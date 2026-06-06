<?php

namespace App\Http\Controllers;

use App\Exports\SupplementaryStudentDetailsExport;
use App\Exports\SupplementaryStudentDetailsExportInternals;

use App\Models\SuplimentarySpecialCaseStudents;
use App\Models\StaffAssi;
use App\Models\Studentuser;
use App\Models\ExamTypes;
use App\Models\ClassModel;
use App\Models\Coeexternals;
use App\Models\Coeinternals;
use App\Models\Gpasetting;
use App\Models\Gradeform;
use App\Models\Gradesetting;
use App\Models\Internalmarks;
use App\Models\Internalweightage;
use App\Models\SemesterMarks;
use App\Models\StaffList;
use App\Models\Modules;
use App\Models\Software;
use App\Models\FailGrades;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\UserActionService;
use App\Services\CoeUpdateLogService;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;

class OBAPController extends Controller
{
    public function index()
    {
        // $data['Section'] = ClassModel::where('Active','ACTIVE')->distinct()->get();

        // return Software::where('id',644)->update(["slink" => "{{route('obap.index')}}"]);
        //  return Software::insert([
        //     "software" => "OBAP",
        //     "role" => "Settings",
        //     "slink" => "{{route('obap.settings')}}",
        //     "simage"=> "bx bxs-report",
        //     "catg"=> "NEW",
        // ]);
        return view('OBAP.index');
    }

    public function COPOMapIndex()
    {
        return view('OBAP.co-po-map-index');
    }

    public function createCOPOMappingIndex()
    {
        // return  DB::table('co_po_mapping')->get();
        $data['sections'] =  ClassModel::where('Active', 'ACTIVE')->distinct()->get();
        return view('OBAP.co-po-mapping-index', $data);
    }

    public function modules(Request $request)
    {
        $modules = StaffAssi::where('sec', $request->section)
            ->select('assimod', 'moddesc')
            ->distinct()
            ->orderBy('assimod')
            ->get();

        return response()->json($modules);
    }

    public function COPOMappingDetails(Request $request)
    {
        // return DB::statement("
        //     CREATE TABLE co_po_mapping (
        //        id INT AUTO_INCREMENT PRIMARY KEY,
        //         section VARCHAR(150),
        //         module VARCHAR(50),
        //         mod_desc VARCHAR(150),
        //         course_staff VARCHAR(150),
        //         staff_department VARCHAR(150),
        //         staff_designation VARCHAR(150),
        //         year INT,
        //         sem INT,
        //        co VARCHAR(50),
        //         po VARCHAR(50),
        //         value INT,
        //         user VARCHAR(150),
        //         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        //     )
        // ");
        $section = $request->section;
        $assimod = $request->assimod;

        if (!$section || !$assimod) {
            return back()->with('error', 'Please select Section and Module.');
        }

        $exist = DB::table('co_po_mapping')
            ->where('section', $section)
            ->where('module', $assimod)
            ->exists();

        if ($exist) {
            return back()->with('error', 'CO-PO Mapping already exists for this Section and Module.');
        }

        $data['staffAssi'] = StaffAssi::where('sec', $section)
            ->where('assimod', $assimod)
            ->first();

        $data['staff'] =  StaffList::where('staffid', $data['staffAssi']->staffid)->first();

        $coPoDetails = DB::table('co_po_set_format')
            ->where('section', $section)
            ->where('module', $assimod)
            ->get();

        $data['class_details'] = ClassModel::where('class', $section)->first();

        $data['cos'] = $coPoDetails->where('type', 'CO')->pluck('type_name')->unique()->sort()->values();
        $data['pos'] = $coPoDetails->where('type', 'PO')->pluck('type_name')->unique()->sort()->values();

        return view('OBAP.co-po-mapping-details', $data);
    }

    public function COPOMappingStore(Request $request)
    {
        foreach ($request->mapping as $co => $poData) {

            foreach ($poData as $po => $value) {



                if (!empty($value)) {

                    DB::table('co_po_mapping')->insert([
                        'section' => $request->section,
                        'module' => $request->module,
                        'mod_desc' => $request->mod_desc,
                        'course_staff' => $request->course_staff,
                        'staff_department' => $request->staff_department,
                        'staff_designation' => $request->staff_designation,
                        'year' =>  $request->year,
                        'sem' => $request->sem,
                        'co'    => $co,
                        'po'    => $po,
                        'value' => $value,
                        'user' => Auth::user()->userId
                    ]);
                }
            }
        }

        return redirect()->route('obap.create-co-po-mapping-index')->with('success', 'Mapping saved successfully');
    }

    // 
    public function viewCOPOMappingIndex()
    {
        $data['sections'] =  DB::table('co_po_mapping')->select('section')->distinct()->get();
        return view('OBAP.view-co-po-mapping-index', $data);
    }


    public function viewModules(Request $request)
    {
        $modules = DB::table('co_po_mapping')->where('section', $request->section)
            ->select('module', 'mod_desc')
            ->distinct()
            ->orderBy('module')
            ->get();

        return response()->json($modules);
    }

    public function viewCOPOMappingDetails(Request $request)
    {
        $section = $request->section;
        $assimod = $request->assimod;

        if (!$section || !$assimod) {
            return back()->with('error', 'Please select Section and Module.');
        }

        $data['staffAssi'] = StaffAssi::where('sec', $section)
            ->where('assimod', $assimod)
            ->first();

        $data['coPoDetails'] = DB::table('co_po_mapping')
            ->where('section', $section)
            ->where('module', $assimod)
            ->get();

        if ($data['coPoDetails']->isEmpty()) {
            return back()->with('error', 'No CO-PO Mapping found.');
        }

        $firstRecord = $data['coPoDetails']->first();

        $data['cos'] = $data['coPoDetails']
            ->pluck('co')
            ->unique()
            ->sort()
            ->values();

        $data['pos'] = $data['coPoDetails']
            ->pluck('po')
            ->unique()
            ->sort()
            ->values();

        $data['class_details'] = (object)[
            'year' => $firstRecord->year,
            'sem'  => $firstRecord->sem,
        ];

        $data['staff'] = (object)[
            'designation' => $firstRecord->staff_designation,
        ];

        return view('OBAP.view-co-po-mapping-details', $data);
    }

    // settings
    public function settingsIndex()
    {
        return view('OBAP.settings.index');
    }

    public function createProgramOutcomeIndex()
    {
        // return StaffAssi::get();
        // return DB::statement("
        //     CREATE TABLE co_po_set_format (
        //         id INT AUTO_INCREMENT PRIMARY KEY,
        //         section VARCHAR(150),
        //         module VARCHAR(150),
        //         mod_desc VARCHAR(150),
        //         type VARCHAR(50),
        //         type_name VARCHAR(150),
        //         type_def VARCHAR(255),
        //         user VARCHAR(50),
        //         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        //     )
        // ");
        // return DB::table('co_po_set_format')->get();
        $data['sections'] =  ClassModel::where('Active', 'ACTIVE')->distinct()->get();
        return view('OBAP.settings.create-pro-outcome-index', $data);
    }

    public function getModules(Request $request)
    {
        $sections = collect($request->sections)
            ->filter(fn($sec) => $sec !== 'ALL')
            ->values()
            ->toArray();

        $modules = StaffAssi::whereIn('sec', $sections)
            ->select('assimod', 'moddesc')
            ->distinct()
            ->orderBy('assimod')
            ->get();

        return response()->json($modules);
    }



    public function createProgramOutcomeStore(Request $request)
    {
        // return $request;
        $request->validate([
            'section'   => 'required|array',
            'assimod'   => 'required|array',
            'type'      => 'required|string',
            'type_name' => 'required|string',
            'type_def'  => 'nullable|string',
        ]);

        foreach ($request->section as $section) {

            foreach ($request->assimod as $module) {

                // Get module description for selected section & module
                $moduleInfo = StaffAssi::where('sec', $section)
                    ->where('assimod', $module)
                    ->select('moddesc')
                    ->first();

                // Prevent duplicate entries
                DB::table('co_po_set_format')->updateOrInsert(
                    [
                        'section'   => $section,
                        'module'    => $module,
                        'type'      => $request->type,
                        'type_name' => $request->type_name,
                    ],
                    [
                        'mod_desc'   => $moduleInfo->moddesc ?? '',
                        'type_def'   => $request->type_def,
                        'user'       => Auth::user()->userId,
                        'created_at' => now(),
                    ]
                );
            }
        }

        return redirect()->back()->with(
            'success',
            $request->type . ' saved successfully.'
        );
    }

    public function programOutcomeView(Request $request)
    {
        // StaffList::where()
        $type = $request->type;

        $records = DB::table('co_po_set_format')
            ->when($type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $item->user  =  StaffList::where('staffid', $item->user)->value('staffname');

                return $item;
            });

        return view(
            'OBAP.settings.view-pro-outcome-view',
            compact('records', 'type')
        );
    }

    public function bulkDelete(Request $request)
    {
        if (!$request->filled('ids')) {
            return back()->with('error', 'Please select at least one record.');
        }

        DB::table('co_po_set_format')
            ->whereIn('id', $request->ids)
            ->delete();

        return back()->with(
            'success',
            count($request->ids) . ' record(s) deleted successfully.'
        );
    }

    // 
    public function createTexonomyIndex()
    {
        // return DB::statement("
        //     CREATE TABLE taxnomy_set_format (
        //         id INT AUTO_INCREMENT PRIMARY KEY,
        //         section VARCHAR(150),
        //         module VARCHAR(150),
        //         mod_desc VARCHAR(150),
        //         type_name VARCHAR(150),
        //         type_def VARCHAR(255),
        //         user VARCHAR(50),
        //         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        //     )
        // ");
        $data['sections'] =  ClassModel::where('Active', 'ACTIVE')->distinct()->get();
        return view('OBAP.settings.create-texo-index', $data);
    }

    public function taxonomyStore(Request $request)
    {
        $request->validate([
            'section'   => 'required|array',
            'assimod'   => 'required|array',
            'type_name' => 'required|string',
            'type_def'  => 'nullable|string',
        ]);

        foreach ($request->section as $section) {

            foreach ($request->assimod as $module) {

                // Get module description for selected section & module
                $moduleInfo = StaffAssi::where('sec', $section)
                    ->where('assimod', $module)
                    ->select('moddesc')
                    ->first();

                // Prevent duplicate entries
                DB::table('taxnomy_set_format')->updateOrInsert(
                    [
                        'section'   => $section,
                        'module'    => $module,
                        'type_name' => $request->type_name,
                    ],
                    [
                        'mod_desc'   => $moduleInfo->moddesc ?? '',
                        'type_def'   => $request->type_def,
                        'user'       => Auth::user()->userId,
                        'created_at' => now(),
                    ]
                );
            }
        }

        return redirect()->back()->with(
            'success',
            $request->type_name . ' saved successfully.'
        );
    }

    public function taxonomyView()
    {
        $records = DB::table('taxnomy_set_format')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $item->user  =  StaffList::where('staffid', $item->user)->value('staffname');

                return $item;
            });

        return view(
            'OBAP.settings.view-taxonomy',
            compact('records')
        );
    }

    public function taxonomybulkDelete(Request $request)
    {
        if (!$request->filled('ids')) {
            return back()->with('error', 'Please select at least one record.');
        }

        DB::table('taxnomy_set_format')
            ->whereIn('id', $request->ids)
            ->delete();

        return back()->with(
            'success',
            count($request->ids) . ' record(s) deleted successfully.'
        );
    }
}
