<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Auth;

class Ticket extends Model
{
    //
    protected $fillable = ['id_customer', 'called_by','phone', 'status','id_categori','tittle','description','assign_to','member','date','time','create_by','created_at','deleted_at'];


    public function tags()
    {
        return $this->belongsToMany(\App\Tag::class, 'tickettags', 'ticket_id', 'tag_id');
    }

    public function user()
    {
        return $this->belongsTo('\App\User', 'assign_to')->withTrashed();
    }
    public function categorie()
    {
        return $this->belongsTo('\App\Ticketcategorie', 'id_categori');
    }
    public function customer()
    {
        return $this->belongsTo('\App\Customer', 'id_customer')->withTrashed();
    }
    public function ticketdetail()
    {

        return $this->hasMany('\App\Ticketdetail', 'id_ticket');
    }

    
    public function status()
    {

        return $this->hasMany('\App\Ticketdetail', 'id_ticket');
    }
    public function my_ticket()
    {
        $id = Auth::user()->id;

        $my_ticket = $this->where('assign_to' , '=', $id)
        ->where('status','!=','Close')
        ->count();
        return $my_ticket;
    }

    public function assignToUser()
    {
        return $this->belongsTo(\App\User::class, 'assign_to', 'id');
    }




}
