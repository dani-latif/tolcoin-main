<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Albums extends Model {

	protected $table = 'albums';

	protected $fillable = array('name');

	public function AlbumhasManyImages(){

		return $this->hasMany('App\AlbumImages');
	}
}