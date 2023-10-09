<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\UserPromotion;
use DB;
use Log;
use Exception;
use DataTables;
use Illuminate\Http\Response;

class UserPromotionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $data = UserPromotion::latest()->get();
                return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn(
                        'action',
                        function ($row) {
                            $edit = '<a href="'.route("promotion_history.edit", $row['id']).'" class="edit btn btn-info btn-sm ">Edit</a><br />';

                            $delete = ''.$edit.'  <form action="'.route("promotion_history.destroy", $row['id']).'" method="POST">
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="_token" value="'.csrf_token().'">
                    <button class="btn btn-primary">Delete User</button>
                </form>';
                            return $delete;
                        }
                    )
                ->rawColumns(['action'])
                ->make(true);
            }
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. ".$e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        try {
            return view('admin.promotions.history.add');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. ".$e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            UserPromotion::create($request->all());
            return redirect('promotions');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. ".$e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        try {
            $data = UserPromotion::find($id);
            return view('admin.promotions.history.edit', compact('data'));
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. ".$e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        try {
            UserPromotion::find($id)->update($request->all());
            return redirect('promotions');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. ".$e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        try {
            UserPromotion::find($id)->delete();
            return redirect('promotions');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. ".$e->getMessage());
            return redirect()->back();
        }
    }
}
