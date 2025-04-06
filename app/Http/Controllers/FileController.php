<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class FileController extends Controller
{
   public function __construct()
   {
    $this->middleware('auth');
}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }
    public function delete($filename)
    {
        $file = public_path('backup/' . $filename);
        if (File::exists($file)) {
            File::delete($file);
            return redirect()->back()->with('success', 'File deleted successfully!');
        } else {
            abort(404, 'File not found');
        }
    }
    public function download($filename)
    {
        $file = public_path('backup/' . $filename);
        if (File::exists($file)) {
            return response()->download($file, $filename);
        } else {
            abort(404, 'File not found');
        }
    }
    public function backup()
    {
        $files = File::files(public_path('backup')); // assuming files are in public/files directory
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        return view('file.index', compact('files'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


      // Validation
      $request->validate([
        'file' => 'required'
    ]); 

      if($request->file('file')) {
       $file = $request->file('file');
       $name = str_replace('-', ' ', $file->getClientOriginalName());
       $filename = time().'_'.$name;

         // File upload location
       $location = 'upload/customerfiles';

         // Upload file
       $file->move($location,$filename);

       $id_customer = ($request['id_customer']);

       \App\File::create([
        'id_customer' => $id_customer,
        'name' =>$file->getClientOriginalName(),
        'path' => $location.'/'.$filename, 

    ]);


       return redirect ('/customer/'.$id_customer)->with('success','Item Updates successfully!');
   }else{
    return redirect ('/customer'.$id_customer)->with('success','File Not Uploaded!');
}

      // return redirect('/');

}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
      \App\File::destroy($id);
      return redirect ('/customer')->with('success','Item deleted successfully!');
  }
}
