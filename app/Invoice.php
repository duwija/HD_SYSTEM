<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class Invoice extends Model
{
    //
    protected $fillable =['id_customer','monthly_fee', 'periode', 'description','qty','amount','created_at','tax', 'tax_amount', 'updated_at','tempcode', 'payment_status', "created_by"];
    
    public function customer()
    {
        return $this->belongsTo('\App\Customer', 'id_customer');
    }
    
    public function checkinv($id)
    {

      $month = now()->format('mY');
      
      
      
      $result = $this->where('id_customer', $id)
      ->where('periode', '=', $month)
      ->Where('monthly_fee','1')
      ->count();
      return $result;

  }
    // public function countinv($id)
    // {
    // 	$mount = now()->format('mY');
    // 	   $count = $this->where('id_customer', $id)
    // 	   // ->where('periode', '=', $mount)
    // 	     ->where('monthly_fee', '=', 1)
    //        ->count();
    //        return $count;
  
    // }
  public function suminvoice()
  {
    return $this->belongsTo(Suminvoice::class, 'tempcode', 'tempcode');
}
public function balanceinv($id,$id_customer)
{
   
    $balance = $this->select(\DB::raw('SUM((qty * amount)) as total'))
    ->where('id_customer', '=', $id_customer)
    ->where('tempcode', $id)->first();
              //  dd($balance['total']);
    return $balance['total'];
    
}
public function encrypt($id)
{

    $encrypted = Crypt::encryptString($id);
    return $encrypted;
}
public static function dencrypt($encrypted)
{

    $decrypted = Crypt::decryptString($encrypted);
    return $decrypted;
}

    //$balance = DB::table('data')->where('user_id' '=' $id)->sum('balance');

}
