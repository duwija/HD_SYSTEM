<?php

namespace App\Http\Controllers;
use DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;


class JurnalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
      $this->middleware('auth');
    }
    public function laporanRugiLaba(Request $request)
    {
    // Ambil tanggal awal dan akhir dari request
      $tanggalAwal = $request->input('tanggal_awal', now()->startOfMonth()->format('Y-m-d'));
      $tanggalAkhir = $request->input('tanggal_akhir', now()->endOfMonth()->format('Y-m-d'));

    // Ambil data pendapatan
      $pendapatan = \App\Akun::with(['transactions' => function ($query) use ($tanggalAwal, $tanggalAkhir) {
        $query->whereBetween('date', [$tanggalAwal, $tanggalAkhir]);
      }])
      ->where('type', 'pendapatan')
      ->get();

    // Hitung saldo awal untuk pendapatan
      foreach ($pendapatan as $akun) {
        $akun->saldo_awal = $akun->transactions()
        ->where('date', '<', $tanggalAwal)
        ->sum(DB::raw('kredit - debet'));
      }

    // Ambil data beban
      $beban = \App\Akun::with(['transactions' => function ($query) use ($tanggalAwal, $tanggalAkhir) {
        $query->whereBetween('date', [$tanggalAwal, $tanggalAkhir]);
      }])
      ->where('type', 'beban')
      ->get();

    // Hitung saldo awal untuk beban
      foreach ($beban as $akun) {
        $akun->saldo_awal = $akun->transactions()
        ->where('date', '<', $tanggalAwal)
        ->sum(DB::raw('debet - kredit'));
      }

    // Hitung total pendapatan
      $totalPendapatan = $pendapatan->reduce(function ($carry, $item) {
        $saldoTransaksi = $item->transactions->sum('kredit') - $item->transactions->sum('debet');
        return $carry + ($item->saldo_awal + $saldoTransaksi);
      }, 0);

    // Hitung total beban
      $totalBeban = $beban->reduce(function ($carry, $item) {
        $saldoTransaksi = $item->transactions->sum('debet') - $item->transactions->sum('kredit');
        return $carry + ($item->saldo_awal + $saldoTransaksi);
      }, 0);

    // Hitung laba/rugi
      $labaRugi = $totalPendapatan - $totalBeban;

    // Return data ke view
      return view('jurnal.rugi_laba', compact('pendapatan', 'beban', 'totalPendapatan', 'totalBeban', 'labaRugi', 'tanggalAwal', 'tanggalAkhir'));
    }
    public function kasbank()
    {
        // Ambil transaksi hari ini
      $today = Carbon::today();
      $transactions = DB::table('jurnals')
      ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'kas & bank') // Filter hanya kategori Kas & Bank
        ->whereDate('jurnals.date', $today)
        ->select('jurnals.*') // Ambil semua kolom dari jurnals
        ->get();

        $transactionsByAccount = DB::table('jurnals')
        ->join('akuns', 'jurnals.id_akun', '=', 'akuns.akun_code')
        ->where('akuns.category', 'kas & bank')
        ->whereDate('jurnals.date', $today)
        ->selectRaw('
          jurnals.id_akun, 
          akuns.name AS akun_name,
          SUM(jurnals.debet) AS total_debit, 
          SUM(jurnals.kredit) AS total_kredit,
          (SUM(jurnals.debet) - SUM(jurnals.kredit)) AS saldo
          ')
        ->groupBy('jurnals.id_akun', 'akuns.name')
        ->get();

        // Hitung posisi kas bank (total debit - total kredit)
        $totalDebit = $transactions->sum('debet');
        $totalKredit = $transactions->sum('kredit');
        $saldo = $totalDebit - $totalKredit;
        
        // Data untuk Chart.js
        $chartData = [
          'labels' => ['Debit', 'Kredit'],
          'datasets' => [
            [
              'label' => 'Posisi Kas Bank',
              'data' => [$totalDebit, $totalKredit],
              'backgroundColor' => ['#28a745', '#dc3545'],
            ],
          ],
        ];

        return view('jurnal/kasbank', compact('transactions', 'saldo', 'chartData','transactionsByAccount'));
      }

      public function index()
      {
        //
        $from=date('Y-m-1');
        $to=date('y-m-d');
      // $jurnal = \App\jurnal::orderBy('id','ASC')
      // ->Where('type','jumum')
      // ->orWhere('type','general')
      // ->get();
        $akuntransaction = \App\Akuntransaction::pluck('name', 'id', 'debet');

        //$accounting = \App\accounting::orderBy('id','ASC')->get();
       //$acccategory = \App\Accountingcategorie::pluck('name', 'id');
        $nsaldo = \App\Jurnal::groupBy('id_akun')->select('id_akun', \DB::raw('sum(debet) as debet'), \DB::raw('sum(kredit) as kredit') )
        ->Where('type','jumum')
        ->orWhere('type','closed')
        ->get();

//       $nrugilaba = \App\Jurnal::groupBy('jurnals.id_akun')->join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//       ->select('jurnals.id_akun','akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
//       ->where(function($query)
//     {
// $query->Where('akuns.group','pendapatan');
//  $query->orWhere('akuns.group','beban');
//      })
//       ->where(function($query)
//      {

//        $query->Where('jurnals.type','jumum');
//        $query->orWhere('jurnals.type','closed');
//      })


//        ->get();
        //  dd($nrugilaba);
        $neraca = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
         $query->Where('akuns.group','aktiva');
         $query->orWhere('akuns.group','utang');
         $query->orWhere('akuns.group','modal');
       })
        ->where(function($query)
        {
         $query->Where('jurnals.type','jumum');
         $query->orWhere('jurnals.type','closed');
       })


        ->groupBy('jurnals.id_akun')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();


       // return view ('jurnal/index',['jurnal' =>$jurnal,'akuntransaction' =>$akuntransaction, 'nsaldo' =>$nsaldo, 'nrugilaba' => $nrugilaba, 'neraca' => $neraca]);

         // return view ('jurnal/index',['akuntransaction' =>$akuntransaction, 'nsaldo' =>$nsaldo, 'nrugilaba' => $nrugilaba, 'neraca' => $neraca]);
        return view ('jurnal/index',['akuntransaction' =>$akuntransaction, 'nsaldo' =>$nsaldo,  'neraca' => $neraca]);
      }

     /**
      * Show the form for creating a new resource.
      *
      * @return \Illuminate\Http\Response
      */


     public function getjurnaldata(Request $request)
     {
      $date_from = $request->input('date_from');
      $date_end = $request->input('date_end');
    $start = $request->start; // Offset awal
    $length = $request->length; // Jumlah data per halaman
    $totalDebet = 0;
    $totalKredit = 0;
    // Query data dari database
   $query = \App\Jurnal::with('akun_name') // Pastikan relasi "akun" sesuai dengan model
   ->whereBetween('date', [$date_from, $date_end])
   ->orderBy('id', 'desc');
   // \Log::info($date_from);
   // \Log::info($date_end);
   // \Log::info($query->toSql());
   // \Log::info($query->getBindings());


    $allTransactions = $query->get()->groupBy('reff'); // Kelompokkan berdasarkan reff

    // Total group sebelum paginasi
    $recordsTotal = $allTransactions->count();

    // Terapkan paginasi (ambil hanya subset data berdasarkan start dan length)
    $paginatedTransactions = $allTransactions->slice($start, $length);
    $totalDebet = $query->sum('debet');
    $totalKredit = $query->sum('kredit');

    $data = [];
    
    $index=$start;

    foreach ($paginatedTransactions as $reff => $transactions) {
        // Header group
      $description = !empty($transactions[0]->reff)
      ? '<a href="/suminvoice/' . $transactions[0]->reff . '">' . $transactions[0]->note . '</a>'
      : $transactions[0]->description;

      $description = str_replace('receive', '', $description);
      $data[] = [
        'index' =>++$index,
        'is_group' => true,
        'reff' => '',
        'description' => $description. '</br><small>'.$transactions[0]->memo.'</small>',
        'user_name' => $transactions[0]->user_name,
        'date' => '',
        'akun_name' => '',
        'debet' => '',
        'kredit' => '',
      ];

        // Detail rows
      foreach ($transactions as $transaction) {
        $data[] = [
          'is_group' => false,
          'reff' => '',
          'description' => '',
          'user_name' => '',
          'date' => $transaction->date,
          'akun_name' => $transaction->akun_name 
          ? $transaction->akun_name->akun_code . ' | ' . $transaction->akun_name->name. '</br><small>'.$transaction->description.'</small>'
          : '',

          'debet' => number_format($transaction->debet, 0, ',', '.'),
          'kredit' => number_format($transaction->kredit, 0, ',', '.'),
        ];
              // $totalDebet += $transaction->debet;
              // $totalKredit += $transaction->kredit;
      }

        // Subtotal row
      $data[] = [
        'is_group' => false,
        'reff' => '',
        'description' => '',
        'user_name' => '',
        'date' => '',
        'akun_name' => 'Subtotal',
        'debet' => number_format($transactions->sum('debet'), 0, ',', '.'),
        'kredit' => number_format($transactions->sum('kredit'), 0, ',', '.'),
      ];
    }

    // Total data setelah filter (sesuaikan jika ada filter tambahan)
    $recordsFiltered = $recordsTotal;

    // Kembalikan JSON respons
    return response()->json([
      'data' => $data,
      'totals' => [
        'debet' => number_format($totalDebet, 0, ',', '.'),
        'kredit' => number_format($totalKredit, 0, ',', '.'),
      ],
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => $recordsFiltered,
    ]);
  }


  public function getBukubesarData(Request $request)
  {
    $date_from = $request->input('date_from');
    $date_end = $request->input('date_end');
    $akun_filter = $request->input('akun_filter'); 
    $start = $request->start; // Offset awal
    $length = $request->length; // Jumlah data per halaman

    function formatSaldo($value)
    {
      return $value < 0 ? '(' . number_format(abs($value), 0, ',', '.') . ')' : number_format($value, 0, ',', '.');
    }

  // Query dasar dengan filter tanggal
    $query = \App\Jurnal::with('akun_name')
    ->whereBetween('date', [$date_from, $date_end]);

    // Tambahkan filter akun jika dipilih
    if (!empty($akun_filter)) {
      $query->where('id_akun', $akun_filter);
    }

    // Query saldo awal (berdasarkan semua transaksi sebelum date_from)
    $saldoAwalQuery = \App\Jurnal::selectRaw('id_akun, SUM(debet - kredit) as saldo_awal')
    ->where('date', '<', $date_from);

    if (!empty($akun_filter)) {
        $saldoAwalQuery->where('id_akun', $akun_filter); // Filter akun untuk saldo awal
      }

      $saldoAwalQuery = $saldoAwalQuery->groupBy('id_akun')->pluck('saldo_awal', 'id_akun');

    // Kelompokkan transaksi berdasarkan id_akun
      $allTransactions = $query->orderBy('id_akun', 'asc')->get()->groupBy('id_akun');
      $recordsTotal = $allTransactions->count();
      $paginatedTransactions = $allTransactions->slice($start, $length);

      $totalDebet = $query->sum('debet');
      $totalKredit = $query->sum('kredit');

      $data = [];
      $index = $start;

      foreach ($paginatedTransactions as $id_akun => $transactions) {
        // Nama akun
        $akunName = $transactions[0]->akun_name
        ? $transactions[0]->akun_name->akun_code . ' | ' . $transactions[0]->akun_name->name
        : 'Akun Tidak Ditemukan';

        // Saldo awal dari query sebelumnya
        $saldoAwal = $saldoAwalQuery[$id_akun] ?? 0;
        $saldo = $saldoAwal;

        // Header grup untuk akun
        $data[] = [
          'index' => ++$index,
          'is_group' => true,
          'reff' => '',
          'description' => $akunName,
          'user_name' => '',
          'date' => '',
          'akun_name' => '',
          'debet' => '',
          'kredit' => '',
          'saldo' => formatSaldo($saldoAwal),
        ];

        // Detail transaksi dalam grup
        foreach ($transactions as $transaction) {
          $saldo += $transaction->debet - $transaction->kredit;

          $data[] = [
            'is_group' => false,
            'reff' => $transaction->reff,
            'description' => $transaction->description,
            'user_name' => $transaction->user_name,
            'date' => $transaction->date,
            'akun_name' =>$transaction->description,
            'debet' => number_format($transaction->debet, 0, ',', '.'),
            'kredit' => number_format($transaction->kredit, 0, ',', '.'),
            'saldo' =>formatSaldo($saldo),
          ];
        }

        // Subtotal untuk grup
        $data[] = [
          'is_group' => false,
          'reff' => '',
          'description' => '',
          'user_name' => '',
          'date' => '',
          'akun_name' => 'Saldo Akhir',
          'debet' => number_format($transactions->sum('debet'), 0, ',', '.'),
          'kredit' => number_format($transactions->sum('kredit'), 0, ',', '.'),
          'saldo' => formatSaldo($saldo),
        ];
      }

      $recordsFiltered = $recordsTotal;

      return response()->json([
        'data' => $data,
        'totals' => [
          'debet' => number_format($totalDebet, 0, ',', '.'),
          'kredit' => number_format($totalKredit, 0, ',', '.'),
        ],
        'recordsTotal' => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
      ]);
    }



    public function jurnal(Request $request)
    {
          // $date_from = $request->date_from ?? date('Y-m-1');
          // $date_end = $request->date_end ?? date('Y-m-d');
          // $date_msg = "Show Data From $date_from to $date_end";

      $akuntransaction = \App\Akuntransaction::pluck('name', 'id');

      return view('jurnal/jumum', compact('akuntransaction'));
    }

    public function bukubesar(Request $request)
    {
    // Default tanggal dan akun
    $from = $request->date_from ?? date('Y-m-01'); // Awal bulan
    $to = $request->date_end ?? date('Y-m-d'); // Hari ini
    $akun_id = $request->akun ?? 1; // Default akun ID = 1

    // Tanggal sebelum periode untuk menghitung saldo awal
    $date_before_from = date('Y-m-d', strtotime('-1 day', strtotime($from)));

    // Pesan tanggal untuk ditampilkan di tampilan
    $date_msg = "Menampilkan data dari $from sampai $to";

    // Ambil saldo awal sebelum periode
    $saldo_awal = \App\Jurnal::where('id_akun', $akun_id)
    ->where('date', '<=', $date_before_from)
    ->whereIn('type', ['jumum', 'closed'])
    ->selectRaw('SUM(debet) - SUM(kredit) AS saldo')
    ->value('saldo') ?? 0;

    // Ambil transaksi selama periode
    $jurnal = \App\Jurnal::where('id_akun', $akun_id)
    ->whereBetween('date', [$from, $to])
    ->whereIn('type', ['jumum', 'closed'])
    ->orderBy('date', 'ASC')
    ->orderBy('id', 'ASC')
    ->get();

    // Ambil daftar akun untuk dropdown
    $akun = \App\Akun::pluck('name', 'akun_code');

    // Ambil daftar akun transaksi (opsional jika digunakan di tampilan)
    $akuntransaction = \App\Akuntransaction::pluck('name', 'id');

    // Return ke view
    return view('jurnal/bukubesar', [
      'jurnal' => $jurnal,
      'saldo_awal' => $saldo_awal,
      'akun' => $akun,
      'akuntransaction' => $akuntransaction,
      'date_msg' => $date_msg,
      'date_from' => $from,
      'date_to' => $to,
      'selected_akun' => $akun_id,
    ]);
  }


  public function kasmasuk()
  {



   $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
   if (empty($parentAkuns)) {
    $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
  }

//    dd($parentAkuns);
  $akunkredit = \App\Akun::whereNotIn('akun_code', $parentAkuns)
  ->whereIn('category', [
    'kas & bank',
    'akun piutang',
    'pendapatan',
    'pendapatan lainnya',
    'ekuitas',
    'akun hutang',
    'kewajiban lancar lainnya',
    'kewajiban jangka panjang'
  ])
  ->get();


  $akundebet = \App\Akun::whereNotIn('akun_code', $parentAkuns)
  ->Where('category','kas & bank')

  ->get();




  return view('jurnal.kasmasuk',['akunkredit'=>$akunkredit,'akundebet'=>$akundebet]);
}

public function kaskeluar()
{
  $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
  if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
          }

          $akundebet = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->whereIn('category', [
            'kas & bank',
            'akun piutang',
            'pendapatan',
            'pendapatan lainnya',
            'ekuitas',
            'akun hutang',
            'kewajiban lancar lainnya',
            'kewajiban jangka panjang'
          ])
          ->get();

          $akunkredit = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->where('category', 'kas & bank')
          ->get();

          return view('jurnal.kaskeluar', [
            'akunkredit' => $akunkredit,
            'akundebet' => $akundebet
          ]);
        }




        public function transferkas()
        {

          $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
          if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
          }
          $akunkredit = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->where('category', 'kas & bank')
          ->get();
          $akundebet = $akunkredit;
          return view('jurnal.tranferkas', [
            'akunkredit' => $akunkredit,
            'akundebet' => $akundebet
          ]);

        }

        public function general()
        {


          $parentAkuns = \App\Akun::whereNotNull('parent')->distinct()->pluck('parent')->toArray();
          if (empty($parentAkuns)) {
            $parentAkuns = [null]; // Set default agar tidak error pada whereNotIn
          }


          $akundk = \App\Akun::whereNotIn('akun_code', $parentAkuns)
          ->get();


          return view ('jurnal/general',['akunkredit' =>$akundk, 'akundebet'=>$akundk]);

        }
// public function kastransaction(Request $request)
// {



//  $request->validate([
//   'akundebet'    => 'required',
//   'type' => 'required|string',
//   'date' => 'required|date',
//   'category' =>'required|string',
//   'contact_id' =>'required|string',
//   'akunkredit'   => 'required|array',
//   'akunkredit.*' => 'required|string', 
//     // 'description_d'  => 'required|string',
//   'description'  => 'required|array',
//   'description.*' => 'nullable|string',
//   'kredit'       => 'required|array',
//   'kredit.*'     => 'required|numeric|min:0',
//   'debet'        => 'required|numeric|min:0',

// ]);
//  $totalKredit = array_sum($request->kredit);
//  if ($totalKredit != $request->debet) {
//   return back()->withErrors(['msg' => 'Total debet dan kredit harus sama!'])->withInput();
// }
// $tempcode=sha1(time().rand());

// $note = $request->type. ' | '.$request->name  ;
// foreach ($request->akunkredit as $index => $akun_kredit) {
//   \App\Jurnal::create([
//     'reff'  => $tempcode,
//     'date' => $request->date,
//     'type'      => $request->type,
//     'id_akun'    => $akun_kredit,
//     'description'     => $request->description[$index] ?? '',
//     'kredit'        => $request->kredit[$index],
//     'note'          => $request->description_d,
//     'category'          => $request->category,
//     'memo'          => $request->memo,
//     'created_by' => \Auth::user()->id,
//     'created_at'    => now(),
//     'updated_at'    => now(),
//   ]);
//   $note = $note. ' | '.$request->description[$index] ?? '';
// }

// \App\Jurnal::create([
//   'reff'  => $tempcode,
//   'date' => $request->date,
//   'id_akun'     => $request->akundebet,
//   'description'     => $request->type,
//   'debet'        => $request->debet,
//   'note'          => $note,
//   'category'          => $request->category,
//   'type'      => $request->type,
//   'created_by' => \Auth::user()->id,
//   'memo'          => $request->memo,
//   'created_at'    => now(),
//   'updated_at'    => now(),
// ]);

// return redirect()->back()->with('success', 'transaction created successfully!');
// }

        public function kasmasuktransaction(Request $request)
        {
          $request->validate([
            'akundebet'    => 'required',
            'type' => 'required|string',
            'date' => 'required|date',
            'category' =>'required|string',
            'contact_id' =>'required|string',
            'akunkredit'   => 'required|array',
            'akunkredit.*' => 'required|string', 
            'description'  => 'required|array',
            'description.*' => 'nullable|string',
            'kredit'       => 'required|array',
            'kredit.*'     => 'required|numeric|min:0',
            'debet'        => 'required|numeric|min:0',
          ]);

          $totalKredit = array_sum($request->kredit);
          if ($totalKredit != $request->debet) {
            return back()->withErrors(['msg' => 'Total debet dan kredit harus sama!'])->withInput();
          }

          $tempcode = sha1(time() . rand());
          $note = $request->type . ' | ' . $request->name;

          DB::beginTransaction();
          try {
            foreach ($request->akunkredit as $index => $akun_kredit) {
              \App\Jurnal::create([
                'reff'  => $tempcode,
                'date' => $request->date,
                'type' => $request->type,
                'id_akun' => $akun_kredit,
                'description' => $request->description[$index] ?? '',
                'kredit' => $request->kredit[$index],
                'note' => $request->description_d,
                'category' => $request->category,
                'memo' => $request->memo,
                'created_by' => \Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now(),
              ]);
              $note .= ' | ' . ($request->description[$index] ?? '');
            }

            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'id_akun' => $request->akundebet,
              'description' => $request->type,
              'debet' => $request->debet,
              'note' => $note,
              'category' => $request->category,
              'type' => $request->type,
              'created_by' => \Auth::user()->id,
              'memo' => $request->memo,
              'created_at' => now(),
              'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaction created successfully!');
          } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
          }
        }


        public function kaskeluartransaction(Request $request)
        {
        // dd($request);
          $request->validate([
            'akunkredit'   => 'required',
            'type' => 'required|string',
            'date' => 'required|date',
            'category' =>'required|string',
            'contact_id' =>'required|string',
            'akundebet'   => 'required|array',
            'akundebet.*' => 'required|string', 
            'description'  => 'required|array',
            'description.*' => 'nullable|string',
            'debet'       => 'required|array',
            'debet.*'     => 'required|numeric|min:0',
            'kredit'        => 'required|numeric|min:0',
          ]);

          $totalDebet = array_sum($request->debet);
          if ($totalDebet != $request->kredit) {
            return back()->withErrors(['msg' => 'Total debet dan kredit harus sama!'])->withInput();
          }

          $tempcode = sha1(time() . rand());
          $note = $request->type . ' | ' . $request->name;

          DB::beginTransaction();
          try {
            foreach ($request->akundebet as $index => $akun_debet) {
              \App\Jurnal::create([
                'reff'  => $tempcode,
                'date' => $request->date,
                'type' => $request->type,
                'contact_id' => $request->contact_id,
                'id_akun' => $akun_debet,
                'description' => $request->description[$index] ?? '',
                'debet' => $request->debet[$index],
                'note' => $request->description_d,
                'category' => $request->category,
                'memo' => $request->memo,
                'created_by' => \Auth::user()->id,
                'created_at' => now(),
                'updated_at' => now(),
              ]);
              $note .= ' | ' . ($request->description[$index] ?? '');
            }

            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'id_akun' => $request->akunkredit,
              'description' => $request->type,
              'kredit' => $request->kredit,
              'note' => $note,
              'category' => $request->category,
              'type' => $request->type,
              'contact_id' => $request->contact_id,
              'created_by' => \Auth::user()->id,
              'memo' => $request->memo,
              'created_at' => now(),
              'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaction created successfully!');
          } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
          }
        }

        public function transferkastransaction(Request $request)
        {

          $request->validate([
            'akunkredit'   => 'required',
            'type' => 'required|string',
            'date' => 'required|date',
            'akundebet' => [
              'required',
              'string',
                 Rule::notIn([$request->akunkredit]), // Tidak boleh sama dengan akunkredit
               ],

               'amount'       => 'required|numeric',
               'memo'         => 'nullable|string'

             ]);
         // Ambil akun debet dan kredit dari database
          $akundebet = \App\Akun::where('akun_code', $request->akundebet)->first();
          $akunkredit = \App\Akun::where('akun_code', $request->akunkredit)->first();
          if (!$akundebet || !$akunkredit) {
            return back()->withErrors(['msg' => 'Akun tidak ditemukan'])->withInput();
          }

          $note = 'Transfer kasbank '.$akunkredit->name.' to '. $akundebet->name;
          $tempcode = sha1(time() . rand());

          DB::beginTransaction();
          try {

            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'type' => $request->type,

              'id_akun' => $request->akundebet,
              'description' => 'Tranfer from '. $akunkredit->name,
              'debet' => $request->amount,
              'note' => $note,
              'category' => 'internal',
              'memo' => $request->memo,
              'created_by' => \Auth::user()->id,
              'created_at' => now(),
              'updated_at' => now(),

            ]);

            \App\Jurnal::create([
             'reff'  => $tempcode,
             'date' => $request->date,
             'type' => $request->type,

             'id_akun' => $request->akunkredit,
             'description' => 'Transfer to '. $akundebet->name,
             'kredit' => $request->amount,
             'note' => $note,
             'category' => 'internal',
             'memo' => $request->memo,
             'created_by' => \Auth::user()->id,
             'created_at' => now(),
             'updated_at' => now(),
           ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transaction created successfully!');
          } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
          }

        }


        public function generaltransaction(Request $request)
        {

         $request->validate([
          'name' => 'required',
          'date' => 'required|date',
          'akun'   => 'required|array',
          'akun.*' => 'required|string', 
          'description'  => 'required|array',
          'description.*' => 'nullable|string',
          'debet'       => 'required|array',
          'kredit'       => 'required|array',
          'debet.*'     => 'required|numeric|min:0',
          'kredit.*'        => 'required|numeric|min:0',
          'memo'         => 'nullable|string'

        ]);

         $type ='general';
         $tempcode = sha1(time() . rand());
         $note = $type . ' TRansaction | ' . $request->name;

         DB::beginTransaction();
         try {
           foreach ($request->akun as $index => $akun_g) {
            \App\Jurnal::create([
              'reff'  => $tempcode,
              'date' => $request->date,
              'type' => $type,
              'contact_id' => $request->contact_id,
              'id_akun' => $akun_g,
              'description' => $request->description[$index] ?? '',
              'debet' => $request->debet[$index],
              'kredit' => $request->kredit[$index],
              'note' => $note,
              'category' => $request->category,
              'memo' => $request->memo,
              'created_by' => \Auth::user()->id,
              'created_at' => now(),
              'updated_at' => now(),
            ]);

          }



          DB::commit();
          return redirect()->back()->with('success', 'Transaction created successfully!');
        } catch (\Exception $e) {
          DB::rollBack();
          return back()->withErrors(['msg' => 'Transaction failed: ' . $e->getMessage()])->withInput();
        }
      }

      public function jpenutup()
      {

        $pendapatan = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.group','pendapatan');
      // $query->orWhere('akuns.group','utang');
      // $query->orWhere('akuns.group','modal');
        })
        ->where(function($query)
        {
          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })


        ->groupBy('jurnals.id_akun')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();



        $beban = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.group','beban');
      // $query->orWhere('akuns.group','utang');
      // $query->orWhere('akuns.group','modal');
        })
        ->where(function($query)
        {
          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })


        ->groupBy('jurnals.id_akun')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();




        $nrugilaba = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.group','pendapatan');
          $query->orWhere('akuns.group','beban');
        })
        ->where(function($query)
        {

          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })

        ->groupBy('jurnals.debet')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();

//dd($nrugilaba);

        $deviden = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
        ->where(function($query)
        {
          $query->Where('akuns.name','deviden');
// $query->orWhere('akuns.group','beban');
        })
        ->where(function($query)
        {

          $query->Where('jurnals.type','jumum');
          $query->orWhere('jurnals.type','closed');
        })

        ->groupBy('jurnals.debet')->select('jurnals.id_akun', 'akuns.name', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit') )
        ->get();



        $akun = \App\Akun::pluck('name', 'id');
        $akuntransaction = \App\Akuntransaction::pluck('name', 'id', 'debet');
        return view ('jurnal/jpenutup',['pendapatan' =>$pendapatan,'beban' =>$beban, 'akuntransaction' =>$akuntransaction,'akun'=>$akun, 'nrugilaba'=>$nrugilaba, 'deviden'=>$deviden ]);



      }
      public function penutup(Request $request)
      {



       DB::beginTransaction();
       try {

        $id=0;
        foreach ($request->akun_id as $akun) {



         \App\Jurnal::create([
          'date' => (date('Y-m-d h:i:sa')),
          'id_akun' => ($akun),
          'debet' => ($request->akun_debet[$id]), 
          'kredit' =>  ($request->akun_kredit[$id]),
          'reff' => uniqid(),
          'type' => ('closed'),
          'description' => ('Jurnal Penutup'),
        ]);


         $id=$id+1;

       }
       DB::commit();
       return redirect ('/jurnal/jpenutup')->with('success','Item created successfully!');

     } catch (Exception $e) {
       // Rollback Transaction
       DB::rollback();
       return redirect ('/jurnal/jpenutup')->with('error','Process Failed!');

       // ada yang error
     }



   }



   public function transaksi(Request $request)
   {
    $utang = "";
    $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
    $transactionname = $request->akuntransaction ? \App\Akuntransaction::where('id', $request->akuntransaction)->first() : '';

    if (!$transactionname) {
      $akundebet = collect();
      $akunkredit = collect();
      return view('jurnal/create', compact('utang', 'akuntransaction', 'akundebet', 'akunkredit', 'transactionname'));
    }

    $akundebet = collect();
    $akunkredit = collect();
    $akundebet = collect();
    $akunkredit = collect();

    switch ($transactionname->name) {
      case "pemasukkan":
      $akunkredit = \App\Akun::where('category', 'pendapatan')->get();
      $akundebet = \App\Akun::whereIn('category', ['kas & bank', 'akun piutang', 'persediaan', 'aktiva lancar lainnya', 'aktiva tetap', 'aktiva lainnya'])->get();
      break;

      case "pengeluaran":
      $akundebet = \App\Akun::whereIn('category', ['harga pokok penjualan', 'beban', 'beban lainnya', 'kas & bank', 'aktiva tetap'])->get();
      $akunkredit = \App\Akun::whereIn('category', ['kas & bank', 'aktiva lancar lainnya'])->get();
      break;

      case "utang":
      $akundebet = \App\Akun::whereIn('category', ['harga pokok penjualan', 'beban', 'beban lainnya', 'aktiva lancar lainnya', 'aktiva tetap'])->get();
      $akunkredit = \App\Akun::whereIn('category', ['akun hutang', 'kewajiban jangka panjang'])->get();
      break;

      case "piutang":
      $akundebet = \App\Akun::where('category', 'akun piutang')->get();
      $akunkredit = \App\Akun::whereIn('category', ['pendapatan', 'pendapatan lainnya', 'kas & bank'])->get();
      break;

      case "bayar utang":
      $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
      ->where('akuns.group', 'utang')
      ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
      ->groupBy('jurnals.reff')
      ->get();
      $akundebet = \App\Akun::whereIn('category', ['akun hutang', 'kewajiban jangka panjang'])->get();
      $akunkredit = \App\Akun::whereIn('category', ['kas & bank', 'aktiva tetap'])->get();
      break;

      case "dibayar piutang":
      $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
      ->where('akuns.name', 'piutang usaha')
      ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
      ->groupBy('jurnals.reff')
      ->get();
      $akunkredit = \App\Akun::where('name', 'piutang usaha')->get();
      $akundebet = \App\Akun::whereIn('category', ['harga pokok penjualan', 'beban', 'beban lainnya', 'pendapatan', 'kas & bank'])->get();
      break;

      case "tambah modal":
      $akundebet = \App\Akun::whereIn('category', ['kas & bank', 'aktiva tetap'])->get();
      $akunkredit = \App\Akun::where('category', 'ekuitas')->get();
      break;

      case "tarik modal":
      $akunkredit = \App\Akun::whereIn('category', ['kas & bank', 'aktiva tetap'])->get();
      $akundebet = \App\Akun::where('category', 'ekuitas')->get();
      break;

      default:
      $jurnal = \App\Jurnal::where('category', 'general')->get();
      $akun = \App\Akun::all();
      return view('jurnal/create', ['jurnal' => $jurnal, 'akun' => $akun, 'akuntransaction' => $akuntransaction]);
    }

    return view('jurnal/create', [
      'utang' => $utang,
      'akundebet' => $akundebet,
      'akunkredit' => $akunkredit,
      'akuntransaction' => $akuntransaction,
      'transactionname' => $transactionname
    ]);
  }



// public function create(Request $request)
// {
//         //
//   $utang ="";
//   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
//   $transactionname = \App\Akuntransaction::Where('id',$request->akuntransaction)->first();

//   if ($transactionname->name == "pemasukkan"){


//     $akunkredit = \App\Akun::Where('type','pendapatan')->get();
//     $akundebet = \App\Akun::Where('type','aktiva lancar')
//     ->orWhere('type','aktiva tetap')
//            // ->orWhere('type','aktiva lancar')
//     ->get();

//     return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
//   }
//   elseif ($transactionname->name == "pengeluaran"){

//    $akundebet = \App\Akun::Where('type','biaya admin dan umum')
//    ->orWhere('type','aktiva lancar')
//    ->orWhere('type','aktiva tetap')
//    ->orWhere('type', 'utang jangka panjang')
//    ->orWhere('type', 'utang jangka pendek')
//    ->get();
//    $akunkredit = \App\Akun::Where('type','aktiva lancar')->get();

//    return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

//  }
//  elseif ($transactionname->name == "utang"){

//    $akundebet = \App\Akun::Where('type','biaya admin dan umum')
//    ->orWhere('type','aktiva lancar')
//    ->orWhere('type','aktiva tetap')
//    ->get();
//    $akunkredit = \App\Akun::Where('type','utang jangka pendek')
//    ->orWhere('type','utang jangka panjang')
//    ->get();

//    return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

//  }

//  elseif ($transactionname->name == "piutang"){

//    $akundebet = \App\Akun::Where('type','aktiva lancar')
//            // ->orWhere('type','aktiva lancar')
//            // ->orWhere('type','aktiva tetap')
//    ->get();
//    $akunkredit = \App\Akun::Where('type','pendapatan')
//    ->orWhere('type','modal')
//    ->orWhere('type','aktiva lancar')
//    ->get();

//    return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

//  }
//  elseif ($transactionname->name == "bayar utang"){

//   $akunutang = \App\Akun::Where('group','utang')->first();
//              // $utang = \App\jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//              // ->Where('akuns.group','utang')
//              // ->select('jurnals.*')

//   $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//   ->Where('akuns.group','utang')
//   ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
//   ->groupBy('jurnals.reff')


//            //  SELECT  id_akun,description, sum(debet) as debet, sum(kredit) as kredit FROM `jurnals` where id_akun=18 group by reff

//               // ->orWhere('type','utang jangka panjang')
//   ->get();
//              //dd($utang);
//   $akundebet = \App\Akun::Where('type','utang jangka pendek')
//   ->orWhere('type','utang jangka panjang')
//   ->get();
//   $akunkredit = \App\Akun::Where('type','biaya admin dan umum')
//   ->orWhere('type','aktiva lancar')
//   ->orWhere('type','aktiva tetap')
//   ->get();

//   return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
// }
// elseif ($transactionname->name == "dibayar piutang"){

//   $akunutang = \App\Akun::Where('name','piutang usaha')->first();
//              // $utang = \App\jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//              // ->Where('akuns.group','utang')
//              // ->select('jurnals.*')

//   $utang = \App\Jurnal::join('akuns', 'akuns.id', '=', 'jurnals.id_akun')
//   ->Where('akuns.name','piutang usaha')
//   ->select('jurnals.id_akun', 'jurnals.reff', 'jurnals.description', \DB::raw('sum(jurnals.debet) as debet'), \DB::raw('sum(jurnals.kredit) as kredit'))
//   ->groupBy('jurnals.reff')


//            //  SELECT  id_akun,description, sum(debet) as debet, sum(kredit) as kredit FROM `jurnals` where id_akun=18 group by reff

//               // ->orWhere('type','utang jangka panjang')
//   ->get();
//              //dd($utang);
//   $akunkredit = \App\Akun::Where('name','piutang usaha')
//               // ->orWhere('type','utang jangka panjang')
//   ->get();
//   $akundebet = \App\Akun::Where('type','biaya admin dan umum')
//   ->orWhere('type','aktiva lancar')
//   ->orWhere('type','aktiva tetap')
//   ->orWhere('type','pendapatan')
//   ->get();

//   return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
// }
// elseif ($transactionname->name == "tambah modal"){

//  $akundebet = \App\Akun::Where('type','aktiva lancar')
//            // ->orWhere('type','aktiva lancar')
//  ->orWhere('type','aktiva tetap')
//  ->get();
//  $akunkredit = \App\Akun::Where('type','modal')->get();

//  return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);

// }
// elseif ($transactionname->name == "tarik modal"){

//  $akunkredit = \App\Akun::Where('type','aktiva lancar')
//            // ->orWhere('type','aktiva lancar')
//  ->orWhere('type','aktiva tetap')
//  ->get();
//  $akundebet = \App\Akun::Where('type','modal')->get();

//  return view ('jurnal/create',['utang' => $utang, 'akundebet' => $akundebet,'akunkredit' => $akunkredit, 'akuntransaction' =>$akuntransaction,'transactionname' =>$transactionname]);
// }
// else {

//           //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
//  $jurnal =\App\Jurnal::Where('type','general')->get();
//          //dd($cjurnal);
//  $akun = \App\Akun::all();
//  return view ('jurnal/general',['jurnal' => $jurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

// }
// }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function closed()
    {
      $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
      $jurnal =\App\Jurnal::Where('type','preclosed')->get();

      $akun = \App\Akun::all();
      return view ('jurnal/closed',['jurnal' => $jurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);



    }


    public function closestore(Request $request)
    {
       // dd ($request);
      //   dd ($request);
 // $type="jcustom";
      $request ->validate([

        'date' => 'required',
        'akun' => 'required|numeric',
        'debetkredit' => 'required',
        'amount' => 'required|numeric',
        'description' => 'required',

      ]);
      if(!empty($request['reff']))
      {
        $reff = $request['reff'];
      }
      else
      {
       $reff = uniqid();
     }

     if($request['debetkredit']=='d')
     {

      \App\Jurnal::create([
        'date' => ($request['date']),
        'id_akun' => ($request['akun']),
        'debet' => ($request['amount']), 
        'reff' => $reff,
        'type' => ($request['type']),
        'description' => ($request['description']),
      ]);
    }
    else
    {
     \App\Jurnal::create([
      'date' => ($request['date']),
      'id_akun' => ($request['akun']),
      'kredit' => ($request['amount']), 
      'reff' => $reff,
      'type' => ($request['type']),
      'description' => ($request['description']),
    ]);

   }


   return redirect ('/jurnal/closed')->with('success','Item created successfully!');


        //
 }




 public function closeupdate(Request $request)
 {
  $id = $request->jurnalid;
  $reff = uniqid();


  DB::beginTransaction();
  try {

    foreach ($id as $id) 
    {

      \App\Jurnal::where('id', $id)->update([
        'type' => 'closed',

        'reff' => $reff,
      ]);

    }
    DB::commit();
    return redirect ('/jurnal')->with('success','Item created successfully!');

  } catch (Exception $e) {
       // Rollback Transaction
   DB::rollback();
   return redirect ('/jurnal')->with('error','Process Failed!');

       // ada yang error
 }




}


public function ccreate()
{

 $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
 $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
         //dd($cjurnal);
 $akun = \App\Akun::all();
 return view ('jurnal/custom',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

}


public function neraca()
{
  $aktiva = \App\Akun::where('group', 'aktiva')
            ->whereNull('parent') // Hanya parent akun
            ->with('children.transactions') // Muat children dan transaksinya
            ->get();

            $kewajiban = \App\Akun::where('group', 'kewajiban')
            ->whereNull('parent')
            ->with('children.transactions')
            ->get();

            $ekuitas = \App\Akun::where('group', 'ekuitas')
            ->whereNull('parent')
            ->with('children.transactions')
            ->get();

            // Hitung total saldo seluruh akun
            $totalAktiva = $aktiva->sum(function ($akun) {
              return $akun->saldo;
            });
            ($totalAktiva);
            $totalKewajiban = $kewajiban->sum(function ($akun) {
              return $akun->saldo;
            });

            $totalEkuitas = $ekuitas->sum(function ($akun) {
              return $akun->saldo;
            });

            $totalSaldo = $totalAktiva + $totalKewajiban + $totalEkuitas;

            return view('jurnal.neraca', compact('aktiva', 'kewajiban', 'ekuitas', 'totalAktiva',
              'totalKewajiban',
              'totalEkuitas',
              'totalSaldo'));
          }
          public function neracaSaldo(Request $request)
          {
    // Ambil input tanggal
            $tanggalAwal = $request->input('tanggal_awal', now()->startOfMonth()->format('Y-m-d'));
            $tanggalAkhir = $request->input('tanggal_akhir', now()->endOfMonth()->format('Y-m-d'));

    // Ambil data akun yang bukan parent
            $akun = \App\Akun::with(['transactions' => function ($query) use ($tanggalAwal, $tanggalAkhir) {
              $query->whereBetween('date', [$tanggalAwal, $tanggalAkhir]);
            }])
    ->whereDoesntHave('children') // Hanya ambil akun yang bukan parent
    ->get();

    // Hitung saldo awal, pergerakan, dan saldo akhir
    $data = $akun->map(function ($item) use ($tanggalAwal, $tanggalAkhir) {
      $saldoAwalDebit = $item->transactions()
      ->where('date', '<', $tanggalAwal)
      ->sum('debet');

      $saldoAwalKredit = $item->transactions()
      ->where('date', '<', $tanggalAwal)
      ->sum('kredit');

      $pergerakanDebit = $item->transactions()
      ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
      ->sum('debet');

      $pergerakanKredit = $item->transactions()
      ->whereBetween('date', [$tanggalAwal, $tanggalAkhir])
      ->sum('kredit');

      $saldoAkhirDebit = $saldoAwalDebit + $pergerakanDebit;
      $saldoAkhirKredit = $saldoAwalKredit + $pergerakanKredit;

      return [
        'kode' => $item->akun_code,
        'nama' => $item->name,
        'saldo_awal_debit' => $saldoAwalDebit,
        'saldo_awal_kredit' => $saldoAwalKredit,
        'pergerakan_debit' => $pergerakanDebit,
        'pergerakan_kredit' => $pergerakanKredit,
        'saldo_akhir_debit' => $saldoAkhirDebit,
        'saldo_akhir_kredit' => $saldoAkhirKredit,
      ];
    });

    return view('jurnal.neraca_saldo', compact('data', 'tanggalAwal', 'tanggalAkhir'));
  }


  public function store(Request $request)
  {
       // dd ($request);
    $type="jumum";
    $request ->validate([

      'date' => 'required',
      'debet' => 'required|numeric',
      'kredit' => 'required|numeric',
      'amount' => 'required|numeric',
      'description' => 'required',

    ]);
    if(!empty($request['reff']))
    {
      $reff = $request['reff'];
    }
    else
    {
     $reff = uniqid();
   }

   \App\Jurnal::create([
    'date' => ($request['date']),
    'id_akun' => ($request['debet']),
    'debet' => ($request['amount']), 
    'reff' => $reff,
    'type' => $type,
    'description' => ($request['description']),
  ]);
   \App\Jurnal::create([
    'date' => ($request['date']),
    'id_akun' => ($request['kredit']),
    'kredit' => ($request['amount']), 
    'reff' => $reff,
    'type' => $type,
    'description' => ($request['description']),
  ]);

   $jurnal = \App\Jurnal::orderBy('date','ASC')->get();
   $akuntransaction = \App\Akuntransaction::pluck('name', 'id', 'debet');

   return redirect ('/jurnal')->with('success','Item created successfully!');


        //
 }

 public function trxstore(Request $request)
 {
    //   dd ($request);
 // $type="jcustom";
  $request ->validate([

    'date' => 'required',
    'akun' => 'required|numeric',
    'debetkredit' => 'required',
    'amount' => 'required|numeric',
    'description' => 'required',

  ]);
  if(!empty($request['reff']))
  {
    $reff = $request['reff'];
  }
  else
  {
   $reff = uniqid();
 }

 if($request['debetkredit']=='d')
 {

  \App\Jurnal::create([
    'date' => ($request['date']),
    'id_akun' => ($request['akun']),
    'debet' => ($request['amount']), 
    'reff' => $reff,
    'type' => ($request['type']),
    'description' => ($request['description']),
  ]);
}
else
{
 \App\Jurnal::create([
  'date' => ($request['date']),
  'id_akun' => ($request['akun']),
  'kredit' => ($request['amount']), 
  'reff' => $reff,
  'type' => ($request['type']),
  'description' => ($request['description']),
]);

}


return redirect ('/jurnal/trxcreate')->with('success','Item created successfully!');


        //
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function trxupdate(Request $request)
    {
      $id = $request->jurnalid;
      $reff = uniqid();


      DB::beginTransaction();
      try {

        foreach ($id as $id) 
        {

          \App\Jurnal::where('id', $id)->update([
            'type' => 'jumum',

            'reff' => $reff,
          ]);

        }
        DB::commit();
        return redirect ('/jurnal')->with('success','Item created successfully!');

      } catch (Exception $e) {
       // Rollback Transaction
       DB::rollback();
       return redirect ('/jurnal')->with('error','Process Failed!');

       // ada yang error
     }




   }
   public function generaldel($id)
   {
    try{
      \App\Jurnal::destroy($id);
      return redirect ('/jurnal/trxcreate')->with('success','Item deleted successfully!');
    }catch (Exception $e) {

     return redirect ('/jurnal/trxcreate')->with('error','Process Failed!');
   }
 }
 public function cupdate(Request $request)
 {
  $id = $request->jcustomid;
  $reff = uniqid();

  foreach ($id as $id) 
  {

    \App\Jurnal::where('id', $id)->update([
      'type' => 'jumum',

      'reff' => $reff,
    ]);

  }

}
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
      try{
        \App\Jurnal::destroy($id);
        return redirect ('/jurnal')->with('success','Item deleted successfully!');
      }catch (Exception $e) {

       return redirect ('/jurnal')->with('error','Process Failed!');
     }
   }

   public function post(Request $request){
    $response = array(
      'status' => 'success',
      'msg' => $request->message,
    );

    return response()->json($response); 
  }





  public function terimakas()
  {

   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
   $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
         //dd($cjurnal);
   $akun = \App\Akun::all();
   return view ('jurnal/terimakas',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

 }
 public function kirimkas()
 {

   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
   $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
         //dd($cjurnal);
   $akun = \App\Akun::all();
   return view ('jurnal/kirimkas',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

 }
 // public function transferkas()
 // {

 //   $akuntransaction = \App\Akuntransaction::pluck('name', 'id');
 //         //$transactionname = \App\akuntransaction::Where('id',$request->akuntransaction)->first();
 //   $cjurnal =\App\Jurnal::Where('type','jcustom')->get();
 //         //dd($cjurnal);
 //   $akun = \App\Akun::all();
 //   return view ('jurnal/transferkas',['cjurnal' => $cjurnal,'akun' => $akun, 'akuntransaction' =>$akuntransaction]);

 // }
}
