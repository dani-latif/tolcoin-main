<?php

namespace App\Http\Controllers;

use App\Http\Requests\Albums\AlbumsIdRequest;
use App\Http\Requests\Albums\DeleteAlbumRequest;
use App\settings;
use Illuminate\Http\Request;
use App\Albums;
use App\AlbumImages;
use DB;
use Illuminate\Support\Facades\Auth;
use File;
use Illuminate\Support\Facades\Cache;

class AlbumsController extends Controller
{
    //
    public function albumsList()
    {
        if (Auth::user()->type == 1 || Auth::user()->type == '1') {
            $title = "Albums";
            $albumsAll = Albums::get();
            return view('albumsList')->with(array('title' => $title, 'albumsAll' => $albumsAll));
        } else {
            return redirect()->back()->with('Errormsg', 'Invalid Link!');
        }
        //'title'=>'Albums',
    }

    public function savealbum(Request $request)
    {
        /* $validator = $this->validate($request,[
            'name'         => 'required',
            //'description'  => 'required',
        ]); */
        $this->validate(
            $request,
            [
                'name' => 'required|max:50',

            ]
        );

        $img = $request->file('photo');
        $image = '';

        if ($img != "") {
            $upload_dir = 'images/gallery';

            $time = date("dhis");

            $image = $time . "_" . $img->getClientOriginalName();

            $move = $img->move($upload_dir, $image);
        } else {
            echo "No Image found";
        }

        if (isset($request['name'])) {
            $name = $request['name'];
            $description = $request['description'];

            if (isset($name) && isset($description)) {
                $albums = new Albums();
                $albums->name = $name;
                $albums->description = $description;
                $albums->cover_image = $image;

                $albums->save();

                $msg = 'Album Save Successfully!';
                return redirect()->back()->with('Successmsg', $msg);
            }
        } else {
            $msg = 'Album Name missing, Please Enter Album Name!';
            return redirect()->back()->with('errormsg', $msg)->withInput();
        }
    }

    public function deleteAlbum($id)
    {
        //dd($id);
        $images = AlbumImages::where('albums_id', $id)->get();
        foreach ($images as $image) {
            $name = $image->image_name;
            $imageid = $image->id;
            $image_path = url('/') . "/images/gallery/" . $name;
            $image_path2 = "/images/gallery/" . $name;  // Value is not URL but directory file path

            if (file_exists($image_path2)) {
                @unlink($image_path2);
            } elseif (File::exists($image_path)) {
                //exit("in side");
                File::delete($image_path);
            }
            AlbumImages::find($imageid)->delete();
        }
        //AlbumImages::where('albums_id',$id)->delete();
        Albums::find($id)->delete();
        return redirect()->back()->with('success', 'Album and all related images removed successfully.');
    }

    public function gallery($id)
    {
        //dd($id);
        $images = AlbumImages::where('albums_id', $id)->get();
        return view('gallery')->with(array('images' => $images, 'albumid' => $id));
        //return view('gallery',compact('images'));
    }

    public function gallery2()
    {
        $AlbumImages = Albums::OrderBy('id', 'DESC')->get();
        return view('home/gallery')->with(array('AlbumImages' => $AlbumImages));
        //return view('gallery',compact('images'));
    }

    public function albumsAll()
    {
        $AlbumImages = Albums::OrderBy('id', 'DESC')->get();
        return view('home/albumsAll')->with(array('AlbumImages' => $AlbumImages));
    }

    public function events_gallery1(AlbumsIdRequest $albumsIdRequest)
    {

        $images1 = AlbumImages::where('albums_id', $albumsIdRequest->id)->get();
        $images2 = AlbumImages::where('albums_id', $albumsIdRequest->id)->first();
        $images3 = Albums::where('id', $albumsIdRequest->id)->first();
        return view('home/events_gallery1')->with(array('images1' => $images1, 'images2' => $images2, 'images3' => $images3));
    }

    public function promotions()
    {
        //dd($id);
        $id = 13;
        $images1 = Cache::remember(config('cachevalue.albumsIdCache'), settings::TopCacheTimeOut, function () use ($id) {
            return AlbumImages::where('albums_id', $id)->orderBy('id', 'desc')->get();
        });

        $images2 = Cache::remember(config('cachevalue.albumsIdFirstCache'), settings::TopCacheTimeOut, function () use ($id) {
            $record = AlbumImages::where('albums_id', $id)->first();
            if (empty($record)) {
                return [];
            } else {
                return $record;
            }
        });

        $images3 = Cache::remember(config('cachevalue.albumsCacheFirst'), settings::TopCacheTimeOut, function () use ($id) {
            $record = Albums::where('id', $id)->first();
            if (empty($record)) {
                return [];
            } else {
                return $record;
            }
        });

        return view('home/events_gallery1')->with(array('images1' => $images1, 'images2' => $images2, 'images3' => $images3));
    }


    public function events_gallery()
    {
        $images = Albums::get();
        return view('home/events_gallery')->with(array('images' => $images));
    }


    public function uploadImages(Request $request)
    {
        $this->validate(
            $request,
            [
                //'title' => 'required',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]
        );

        $upload_dir = 'images/gallery';
        $img = $request->file('image');
        $uploadId = $request['albumId'];
        //dd($uploadId);
        if ($img != "") {
            $image_cname = time() . "-" . $img->getClientOriginalName();
            $imgName = $img->getClientOriginalName();
            //$move1                = $img->move($upload_dir, $imgName);
            //$image_cext              = time().".".$img->getClientOriginalExtension();
            $move2 = $img->move($upload_dir, $image_cname);
            $todayDate = DATE("Y-m-d");
            $input['title'] = $imgName;
            $input['description'] = $todayDate;
            $input['image_name'] = $image_cname;
            $input['albums_id'] = $uploadId;
            AlbumImages::create($input);
        } else {
            //return back()->with('success','Image Uploaded successfully.');
            return response()->json(['error', 'Image Uploaded not successful.']);
        }

        //return back()->with('success','Image Uploaded successfully.');
        return response()->json(['success', 'Image Uploaded successfully.']);
    }


    /*
     * Delete Image function
     * @return \Illuminate\Http\Response
     */

    public function deleteimage(DeleteAlbumRequest $deleteAlbumRequest)
    {
        $image_path = url('/') . "/images/gallery/" . $deleteAlbumRequest->name;
        $image_path2 = "/images/gallery/" . $deleteAlbumRequest->name;  // Value is not URL but directory file path

        if (file_exists($image_path2)) {
            @unlink($image_path2);
        } elseif (File::exists($image_path)) {
            //exit("in side");
            File::delete($image_path);
        }
        \Illuminate\Support\Facades\DB::table('album_images')->find($deleteAlbumRequest->id)->delete();
        return back()->with('success', 'Image removed successfully.');
    }

    public function updateAlbmdetails(Request $request)
    {
        $albums_id1 = $request['id'];

        $albmdetails1 = Albums::where('id', $albums_id1)->first();


        if (isset($request['name'])) {
            $name = $request['name'];
        } else {
            $name = $albmdetails1->name;
        }

        if (isset($request['description'])) {
            $description = $request['description'];
        } else {
            $description = $albmdetails1->description;
        }


        $img = $request->file('cover_image');
        $image = '';

        if ($img != "") {
            $upload_dir = 'images/gallery';

            $time = date("dhis");

            $image = $time . "_" . $img->getClientOriginalName();

            $move = $img->move($upload_dir, $image);
        } else {
            $image = $albmdetails1->cover_image;
        }


        if (isset($name) && isset($description)) {
            /*  $albums                    =    Albums::where('id',$albums_id1)->first();
            $albums->name            =     $name;
            $albums->description    =     $description;
            $albums->cover_image    =     $image;

            $albums->save();



            Albums::find($albums_id1)->save();
                                           */

            $results = Albums::where('id', $request['id'])
                ->update(
                    [

                        'name' => $name,
                        'description' => $description,
                        'cover_image' => $image

                    ]
                );


            $msg = 'Album Update Successfully!';
            return redirect()->back()->with('Successmsg', $msg);
        } else {
            $msg = 'Album Name missing, Please Enter Album Name!';
            return redirect()->back()->with('errormsg', $msg)->withInput();
        }
    }

    public function albmdetails(Request $request)
    {
        $albums_id = $request['id'];

        $albmdetails = Albums::where('id', $albums_id)->first();

        $details = ' <div class="form-group">
                            <label for="name" class="col-md-4 control-label">Album Name</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control" name="name" value="' . $albmdetails->name . '"> </div>
                        </div>

                        <div class="form-group">
                            <label for="description" class="col-md-4 control-label">Album Details</label>

                            <div class="col-md-6">
                                <input id="description" type="text" class="form-control" name="description" value="' . $albmdetails->description . '">

                               
                            </div>
                        </div>


                         <div class="form-group">
                            <label for="Picture" class="col-md-4 control-label">Album Picture</label>

                            <div class="col-md-6">


								<input type="file" name="cover_image" value="fileupload">
                               <img  style="width:60px; height:80px; float: right;  " class="img-responsive" src="' . asset("/images/gallery/" . $albmdetails->cover_image) . '" />
                            </div>
                        </div>


                        

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                            <input type="hidden" name="id" value="' . $albums_id . '">
								<input type="hidden" name="_token" value="' . csrf_token() . '">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-btn fa-user"></i> Save
                                </button>
                            </div>
                        </div>';


        echo $details;
    }
}
