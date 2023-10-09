<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class AlbumImages extends Model {
	  
	protected $table = 'album_images';
	  
	protected $fillable = array('albums_id','image_name');
	  
	public function Albums(){

		return $this->belongsTo('App\Albums');
	}
}