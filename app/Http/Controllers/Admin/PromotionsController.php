<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Promotions\PromotionsIdRequest;
use App\User;
use App\UserPromotion;
use Google\CRC32\PHP;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Promotion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Response;

class PromotionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|Response|\Illuminate\View\View
     * @throws AuthorizationException
     */
    public function index(Request $request)
    {

        $this->authorize('viewAny', Promotion::class);
        try {
            if ($request->ajax()) {
                $data = Promotion::latest()->get();
                return Datatables::of($data)
                    ->addIndexColumn()
                    ->addColumn(
                        'action',
                        function ($row) {
                            $edit = '<a href="' . route("promotions.edit", $row['id']) . '" class="edit btn btn-info btn-sm ">Edit</a><br />';
                            if ($row['status'] == 'active') {
                                $btn = '' . $edit . ' <a href="' . route("promotion.disable", $row['id']) . '" class="edit btn btn-danger btn-sm ">Disable</a>';
                            } else {
                                $btn = '' . $edit . ' <a href="' . route("promotion.active", $row['id']) . '" class="edit btn btn-success btn-sm ">Activate</a>';
                            }

                            return $btn;
                        }
                    )
                    ->rawColumns(['action'])
                    ->make(true);
            }
            return view('admin.promotions.index');
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error("Something went wrong. Please try again later. " . $e->getMessage());
            return redirect()->back();
        }
    }

    public function importUsersList(){
        $promos = Promotion::get();
        return view('admin.promotions.import',compact('promos'));
    }

    public function importPromoUsersList(Request $request){
        $content = file_get_contents($request->fileurl);
        $lines = explode(PHP_EOL,$content);
        foreach ($lines as $line){
            $columns = str_getcsv($line);
            $userUID = $columns[0];
            $userId = User::whereUId($userUID)->first();
            $promo = Promotion::find($request->promo_name);
            if($userUID && $userId) {
               //Inserting Data into Promotions Table
                $userPromo = new UserPromotion();
                $userPromo->user_id = $userId->id;
                $userPromo->user_u_id = $userUID;
                $userPromo->promo_id = $promo->id;
                $userPromo->name	 = $columns[1];
                $userPromo->country = $columns[2];
                $userPromo->plan_id = $columns[3];
                $userPromo->level_1 = $columns[4];
                $userPromo->level_2 = $columns[5];
                $userPromo->level_3 = $columns[6];
                $userPromo->level_4 = $columns[7];
                $userPromo->level_5 = $columns[8];
                $userPromo->total_investment = $columns[9];
                $userPromo->from = $promo->start_date;
                $userPromo->to = $promo->end_date;
                $userPromo->save();
            }
        }
        echo "</br> Import " . count($lines) . " users Successfully </br>
             <a href=" . url('importUsersList') . ">Go Back</a>";
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        try {
            return view('admin.promotions.add');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. " . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        try {
            Promotion::create($request->all());
            return redirect('promotions');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. " . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        try {
            $data = Promotion::find($id);
            return view('admin.promotions.edit', compact('data'));
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. " . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        try {
            Promotion::find($id)->update($request->all());
            return redirect('promotions');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. " . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Update the status of the promotion from disable to active.
     *
     * @param PromotionsIdRequest $promotionsIdRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|Response|\Illuminate\Routing\Redirector
     */
    public function activate(PromotionsIdRequest $promotionsIdRequest)
    {
        try {
            DB::table('promotions')->find($promotionsIdRequest->id)->update(['status' => 'active']);
            return redirect('promotions');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. " . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Update the status of the promotion from active to disable.
     *
     * @param PromotionsIdRequest $promotionsIdRequest
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|Response|\Illuminate\Routing\Redirector
     */
    public function disable(PromotionsIdRequest $promotionsIdRequest)
    {
        try {
            DB::table('promotions')->find($promotionsIdRequest->id)->update(['status' => 'disable']);
            return redirect('promotions');
        } catch (Exception $e) {
            Log::error("Something went wrong. Please try again later. " . $e->getMessage());
            return redirect()->back();
        }
    }

    public function getDetailById(Request $request)
    {
        if (is_numeric($request->id)) {
            return getUserDetailsById($request->id);
        } else {
            return getUserDetails($request->id);
        }
    }
}
