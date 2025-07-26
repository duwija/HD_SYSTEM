<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Jurnal extends Model
{
  use SoftDeletes;
    //
  protected $fillable =['date','id_akun', 'kredit', 'debet','reff','type','description',"created_by",'deleted_at','note','created_by', 'supplier_id','category','memo'];

  public function akun_name()
  {
    return $this->belongsTo('\App\Akun', 'id_akun');
  }

}
