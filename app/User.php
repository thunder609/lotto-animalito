<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /** Niveles de usuario */
    const LEVEL_ADMIN = 1;
    const LEVEL_USER = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'identity_card', 'bank_id',
        'number_account', 'balance', 'block_balance', 'level',
        'password_temp', 'password_temp_expiration',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'password_temp', 'password_temp_expiration',
    ];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->balance = 0;
        $this->block_balance = 0;
        $this->level = self::LEVEL_USER;
        parent::__construct($attributes);
    }

    /**
     * Todas las transferencias del usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transfers()
    {
        return $this->hasMany('App\Transfer', 'user_id');
    }

    /**
     * Banco configurado por el usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bank()
    {
        return $this->belongsTo('App\Bank', 'bank_id');
    }

    /**
     * Lista de tickets registrados por el usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets()
    {
        return $this->hasMany('App\Ticket', 'user_id');
    }

    /**
     * Todos los retiros solicitados por el usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function withdraws()
    {
        return $this->hasMany('App\Withdraw', 'user_id');
    }

    /**
     * Calcula el balance real
     *
     * @return int
     */
    public function realBalance()
    {
        return $this->balance - $this->block_balance;
    }

    /**
     * Retorna las ganancias de la semana para este usuario
     *
     * @return array
     */
    public function graphicData()
    {
        $now = new \DateTime();
        $oneMonthBefore = clone $now;
        $oneMonthBefore->modify('-1 month');

        // Busco todos los tickets del mes para este usuario
        $tickets = Ticket::where('created_at', '>=', $oneMonthBefore)
            ->where('user_id', $this->id)
            ->orderBy('created_at')
            ->get()
        ;

        $data['categories'] = [];
        $data['data'] = [];
        $c = -1;

        foreach ($tickets as $ticket) {
            $date = $ticket->created_at->format('d-m-Y');

            if (! in_array($date, $data['categories'])) {
                // Agrego la fecha como categoria
                $data['categories'][] = $date;
                $c++;
                $data['data'][$c] = 0;
            }

            $data['data'][$c] += $ticket->gainAmount();
        }

        return $data;
    }

    /**
     * Todas las notificaciones de este usuario
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notifications()
    {
        return $this->hasMany('App\Notification', 'user_id');
    }

    /**
     * Ultimas notificaciones
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getLastNotifications($limit = 5)
    {
        return $this->notifications()->orderBy('id', 'DESC')->limit($limit)->get();
    }

    /**
     * Cuenta las notificaciones sin leer para este usuario
     *
     * @return int
     */
    public function countNotificationUnread()
    {
        return $this->notifications()->where('status', Notification::STATUS_UNREAD)->count();
    }

    /**
     * Marca como leidas todas las notificaciones
     */
    public function readNotifications()
    {
        $this->notifications()->update(['status' => Notification::STATUS_READ]);
    }

    /**
     * Genera una notificacion para este usuario
     *
     * @param $message
     * @param $url
     */
    public function generateNotification($message, $url)
    {
        $notification = new Notification();
        $notification->message = $message;
        $notification->url = $url;
        $notification->user_id = $this->id;
        $notification->status = Notification::STATUS_UNREAD;
        $notification->save();
    }

    /**
     * Genera una notificacion para todos los administradores
     *
     * @param $message
     * @param $url
     */
    public function generateAdminNotification($message, $url)
    {
        $admins = User::where('level', self::LEVEL_ADMIN)->get();

        foreach ($admins as $user) {
            $notification = new Notification();
            $notification->message = $message;
            $notification->url = $url;
            $notification->user_id = $user->id;
            $notification->status = Notification::STATUS_UNREAD;
            $notification->save();
        }
    }
}
