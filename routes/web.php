<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappController;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//s
// Route::get('/', function () {
//     return view('/home');
// });

Route::get('/winpay','SuminvoiceController@winpay');
Route::post('/create-winpay-va','SuminvoiceController@createWinpayVA');



Route::get('/', 'HomeController@index')->name('home');
Route::get('/warestart', 'HomeController@warestart');


Route::get('/schedule', 'HomeController@schedule');
Route::get('/schedule-refresh', 'HomeController@scheduleRefresh');

Route::get('/homex', 'HomeController@mikrotik_addsecreate');
Route::get('/homey', 'HomeController@mikrotik_disablesecreate');
Route::get('/homez', 'HomeController@mikrotik_status');
Route::get('/homexy', 'HomeController@wa');
Route::get('/xendit', 'HomeController@xendit');
Route::get('/halo', 'PagesController@halo');
Route::get('/customer/mapdata', 'CustomerController@mapData');
Route::patch('/customer/restore/{id}','CustomerController@restore');
Route::post('/customer/table_customer','CustomerController@table_customer');
Route::post('/customer/table_customermerchant','CustomerController@table_customermerchant');
Route::post('/customer/table_unpaid_customer','CustomerController@table_unpaid_customer');
Route::post('/customer/table_isolir_customer','CustomerController@table_isolir_customer');
Route::post('/customer/table_plan_group','CustomerController@table_plan_group');
Route::get('/customer/trash','CustomerController@trash');
Route::get('/customer','CustomerController@index');
Route::get('/customer/log/{id}','CustomerController@log');
Route::get('/customer/unpaid','CustomerController@unpaid');
Route::get('/customer/isolir','CustomerController@isolir');
Route::get('/customer/create','CustomerController@create');
Route::get('/customer/search','CustomerController@search');
Route::post('/customer/filter','CustomerController@filter');
Route::post('/customer','CustomerController@store');
Route::post('/customer/{id}/file','CustomerController@uploadFile');
Route::post('/customer/wa','CustomerController@wa_customer');
Route::patch('/customer/update/status','CustomerController@update_status');
Route::patch('/customer/update/status_2','CustomerController@update_status_2');
Route::get('/customer/{id}','CustomerController@show');
Route::get('/customer/{id}/edit','CustomerController@edit');
Route::patch('/customer/{id}','CustomerController@update');
Route::delete('/customer/{id}','CustomerController@destroy');
Route::post('/customer/searchforjurnal', 'CustomerController@searchforjurnal');
Route::get('/customermerchant','CustomerController@customermerchant');
Route::post('/customer/createtunnel', 'CustomerController@createtunnel');
Route::get('/customer/{id}/router-status', 'CustomerController@ajaxRouterStatus');

Route::get('/subscribe/{customerId}', 'CustomerController@subscribeform');
Route::get('/pendaftaran/pdf/{id}', 'ustomerController@cetakPDF');
Route::post('/pendaftaran', 'CustomerController@generatePDF');


Route::get('/ticket/datamap', 'TicketController@datamap');

Route::get('/vendorticket','VendorController@vendorticket');
Route::post('/ticket/table_vendorticket_list','VendorController@table_vendorticket_list');
Route::get('/vendorticket/{id}','VendorController@vendorshow');

Route::get('/ticket/report','TicketController@report');
Route::post('/ticket/reportsrc','TicketController@report');
Route::get('/ticket','TicketController@index');
Route::get('/ticket/groupticket','TicketController@groupticket');
Route::get('/ticket/vendorgroupticket','VendorController@groupticket');
Route::get('/myticket','TicketController@myticket');

Route::get('/uncloseticket','TicketController@uncloseticket');
Route::post('/ticket/table_myticket_list','TicketController@table_myticket_list');

Route::post('/ticket/table_ticket_list','TicketController@table_ticket_list');
Route::post('/ticket/table_groupticket_list','TicketController@table_groupticket_list');
Route::post('/ticket/table_vendorgroupticket_list','VendorController@table_vendorgroupticket_list');

Route::get('/ticket/{id}/create','TicketController@create');
Route::get('/ticket/{id}/edit','TicketController@edit');

Route::get('/ticket/{id}','TicketController@show');

Route::get('/ticket/view/{id}','TicketController@view');
Route::get('/ticket/print/{id}','TicketController@print_ticket');


Route::get('/sale','SaleController@index');
Route::get('/sale/create','SaleController@create');
Route::post('/sale','SaleController@store');
Route::get('/sale/{id}/edit','SaleController@edit');
Route::patch('/sale/customer/{id}','SaleController@customer');
Route::get('/sale/{id}','SaleController@show');
Route::delete('/sale/{id}','SaleController@destroy');
Route::post('/sale/table_sale_customer','SaleController@table_sale_customer');



Route::post('/tag/store','TagController@store');



Route::post('/ticket','TicketController@store');
Route::post('/ticket/search','TicketController@search');
Route::patch('/ticket/{id}/vendoreditticket','VendorController@vendoreditticket');
Route::patch('/ticket/{id}/editticket','TicketController@editticket');
Route::patch('/ticket/{id}/assign','TicketController@updateassign');
Route::post('/ticket/wa_ticket','TicketController@wa_ticket');


Route::post('/ticketdetail','TicketdetailController@store');
Route::post('/invoice/mounthlyfee','InvoiceController@createmounthlyinv');
Route::get('/invoice/bulk','InvoiceController@invoicehandle');
Route::get('/invoice/createinv','CustomerController@createinv');
// Route::get('/invoice/make','InvoiceController@index');

Route::get('/payment','PaymentController@search');
Route::post('/payment/show','PaymentController@show');


Route::post('suminvoice/remainderinv/{id}','SuminvoiceController@send_reminder_inv');

Route::post('/invoice/table_invoice_list','InvoiceController@table_invoice_list');
Route::post('/customer/table_invoice','CustomerController@table_invoice');
Route::get('/invoice','InvoiceController@index');
Route::get('/invoice/{id}','InvoiceController@show');
Route::get('/invoice/{id}/edit','InvoiceController@edit');
Route::get('/invoice/cst/{id}','InvoiceController@custinv');
Route::get('/invoice/{id}/create','InvoiceController@create');
Route::post('/invoice','InvoiceController@store');
Route::post('/invoice/table_invoice','InvoiceController@table_invoice');

Route::get('/invoice/{id}/delete/{cid}','InvoiceController@destroy');
Route::post('/suminvoice/table_transaction_list','SuminvoiceController@table_transaction_list');
Route::get('/suminvoice/notification','SuminvoiceController@notification');
Route::get('/suminvoice/invoicenotif','SuminvoiceController@invoicenotif');
Route::post('/suminvoice/createinvoice','InvoiceController@createinvoice');
Route::get('/suminvoice','SuminvoiceController@index');
Route::get('/suminvoice/transaction','SuminvoiceController@transaction');
Route::post('/suminvoice/transaction','SuminvoiceController@searchtransaction');
Route::get('/suminvoice/mytransaction','SuminvoiceController@mytransaction');
Route::post('/suminvoice/mytransaction','SuminvoiceController@searchmytransaction');
Route::post('/suminvoice/verify/{id}','SuminvoiceController@verify');

Route::get('/suminvoice/testinv','SuminvoiceController@invtest');
Route::get('/suminvoice/{id}','SuminvoiceController@show');

Route::get('/testwa','SuminvoiceController@testwa');

Route::get('/suminvoice/{id}/print','SuminvoiceController@print');
Route::get('/suminvoice/{id}/viewinvoice','SuminvoiceController@print');
Route::get('/suminvoice/{id}/dotmatrix','SuminvoiceController@dotmatrix');
Route::post('/suminvoice','SuminvoiceController@store');
Route::post('/suminvoice/search','SuminvoiceController@search');
Route::post('/suminvoice/find','SuminvoiceController@searchinv');
Route::patch('/suminvoice/{id}','SuminvoiceController@update');
Route::patch('/suminvoice/{id}/faktur','SuminvoiceController@faktur');
Route::delete('/suminvoice/{id}','SuminvoiceController@destroy');
//Route::post('/suminvoice/xendit',function (){})->middleware(['xenditauth']);
Route::post('/xenditcallback/invoice','XenditCallbackController@update')->middleware(['xenditauth']);
Route::post('/tripay/callback','XenditCallbackController@update_tripay');
Route::post('/tripay/create','SuminvoiceController@tripay');

Route::post('/winpay/callback','XenditCallbackController@update_winpay');


//Jobs
Route::post('/jobs/notifinv','SuminvoiceController@notifinvJob');
Route::get('/jobs/customerblockednotifjob','SuminvoiceController@customerblockednotifJob');
Route::post('/jobs/customerisolirjob','SuminvoiceController@customerisolirJob');
Route::post('/jobs/customerinvjob','SuminvoiceController@createinvmonthlyJob');
Route::get('/jobs/isolirdata','SuminvoiceController@isolirData');
Route::post('/jobs/getSelectedcustomermerchant','SuminvoiceController@getSelectedcustomermerchant');
Route::get('/jobs/getSelectedblocknotif','SuminvoiceController@getSelectedblocknotif');
Route::get('/jobs/getSelectedunpaidnotif','SuminvoiceController@getSelectedunpaidnotif');

//Tools
Route::get('/tool/burstcalc','ToolController@burstcalc');
Route::post('tool/macvendor', 'ToolController@maclookup');
Route::get('tool/macvendor', 'ToolController@macvendor');
Route::post('tool/ipcalc', 'ToolController@ipcalc');
Route::get('tool/ipcalc', 'ToolController@showipcalc');
// routes/web.php

//Supplier
Route::post('/contact/table_contact_list','ContactController@table_contact_list');
Route::get('/contact','ContactController@index');
Route::get('/contact/getcontactinfo/{id}','ContactController@getcontactinfo');
Route::get('/contact/create','ContactController@create');
Route::post('/contact','ContactController@store');
Route::get('/contact/{id}/edit','ContactController@edit');
Route::patch('/contact/{id}','ContactController@update');
Route::get('/contact/{id}','ContactController@show');
Route::delete('/contact/{id}','ContactController@destroy');
//Route::get('/gettotalakun/{id} ', 'ContactController@gettotalakun');
Route::post('/contact/searchforjurnal', 'ContactController@searchforjurnal');

//Merchant
Route::post('/merchant/table_merchant_list','MerchantController@table_merchant_list');
Route::get('/merchant','MerchantController@index');
Route::get('/merchant/getmerchantinfo/{id}','MerchantController@getmerchantinfo');
Route::get('/merchant/create','MerchantController@create');
Route::post('/merchant','MerchantController@store');
Route::get('/merchant/{id}/edit','MerchantController@edit');
Route::patch('/merchant/{id}','MerchantController@update');
Route::get('/merchant/{id}','MerchantController@show');
Route::delete('/merchant/{id}','MerchantController@destroy');
Route::get('/gettotalakun/{id} ', 'MerchantController@gettotalakun');

//User

Route::get('/user','UserController@index');
Route::get('/user/create','UserController@create');
Route::post('/user','UserController@store');
Route::get('/user/log','UserController@log');
Route::get('/user/{id}/edit','UserController@edit');
Route::patch('/user/{id}','UserController@update');
Route::delete('/user/{id}','UserController@destroy');
Route::get('/user/{id}/myprofile','UserController@myprofile');
Route::post('/user/searchforjurnal', 'UserController@searchforjurnal');


//Ovetime

Route::get('/overtime','OvertimeController@index');
Route::get('/overtime/create','OvertimeController@create');
Route::get('/overtime/{id}/edit','OvertimeController@edit');
Route::get('/overtime/{id}','OvertimeController@null');
Route::post('/overtime','OvertimeController@store');
Route::delete('/overtime/{id}','OvertimeController@destroy');
Route::patch('/overtime/{id}','OvertimeController@update');

//Plan

Route::get('/plan','PlanController@index');
Route::get('/plan/create','PlanController@create');
Route::get('/plan/{id}/edit','PlanController@edit');
Route::get('/plan/{id}','PlanController@null');
Route::post('/plan','PlanController@store');
Route::delete('/plan/{id}','PlanController@destroy');
Route::patch('/plan/{id}','PlanController@update');

Route::get('/distpoint/map', 'DistpointController@showMap');
Route::get('/distpoint/data', 'DistpointController@getODPData');

Route::post('/distpoint/table_distpoint_list','DistpointController@table_distpoint_list');
Route::get('/distpoint','DistpointController@index');
Route::get('/distpoint/create','DistpointController@create');
Route::post('/distpoint','DistpointController@store');
Route::get('/distpoint/{id}/edit','DistpointController@edit');
Route::patch('/distpoint/{id}','DistpointController@update');
Route::get('/distpoint/{id}','DistpointController@show');
Route::delete('/distpoint/{id}','DistpointController@destroy');

Route::post('/distpointgroup/table_distpointgroup_list','DistpointgroupController@table_distpointgroup_list');
Route::get('/distpointgroup','DistpointgroupController@index');
Route::get('/distpointgroup/create','DistpointgroupController@create');
Route::post('/distpointgroup','DistpointgroupController@store');
Route::get('/distpointgroup/{id}/edit','DistpointgroupController@edit');
Route::patch('/distpointgroup/{id}','DistpointgroupController@update');
Route::get('/distpointgroup/{id}','DistpointgroupController@show');
Route::delete('/distpointgroup/{id}','DistpointgroupController@destroy');


Route::get('/distrouter','DistrouterController@index');
Route::post('/distrouter/executeCommand','DistrouterController@executeCommand');

Route::get('/distrouter/logs/{id}', 'DistrouterController@getMikrotikLogs');
Route::get('/distrouter/getPppoeUsers/{id}/{status}', 'DistrouterController@getPppoeUsers');
Route::get('/distrouter/getrouterinfo/{id}','DistrouterController@getrouterinfo');
Route::get('/distrouter/getrouterinterfaces/{id}','DistrouterController@getrouterinterfaces');
Route::get('/distrouter/interface_monitor/{id}','DistrouterController@interfacemonitor');
Route::get('/distrouter/backupconfig/{id}','DistrouterController@backupsconfig');


Route::get('/distrouter/create','DistrouterController@create');
Route::post('/distrouter','DistrouterController@store');
Route::get('/distrouter/{id}/edit','DistrouterController@edit');
Route::patch('/distrouter/{id}','DistrouterController@update');
Route::get('/distrouter/{id}','DistrouterController@show');
Route::delete('/distrouter/{id}','DistrouterController@destroy');

Route::get('bank','BankController@index');
Route::get('/bank/create','BankController@create');
Route::get('/bank/{id}/edit','BankController@edit');
Route::get('/bank/{id}','BankController@null');
Route::post('/bank','BankController@store');
Route::delete('/bank/{id}','BankController@destroy');
Route::patch('/bank/{id}','BankController@update');

Route::get('device','DeviceController@null');
Route::get('/device/{id}','DeviceController@index');
Route::post('/device','DeviceController@store');
Route::patch('/device/{id}','DeviceController@update');
Route::delete('/device/{cust}/{id}','DeviceController@destroy');

Route::get('accounting','accountingController@index');
// Route::get('/accounting/{id}','accountingController@index');
Route::post('/accounting','accountingController@store');
Route::patch('/accounting/{id}','accountingController@update');

Route::delete('/accounting/{cust}/{id}','accountingController@destroy');


//Route::get('jurnal/kas', 'JurnalController@kas');
Route::post('jurnal/kasmasuktransaction', 'JurnalController@kasmasuktransaction');
Route::post('jurnal/kaskeluartransaction', 'JurnalController@kaskeluartransaction');
Route::post('jurnal/transferkastransaction', 'JurnalController@transferkastransaction');
Route::post('jurnal/generaltransaction', 'JurnalController@generaltransaction');
Route::get('jurnal/kasbank', 'JurnalController@kasbank');
Route::get('jurnal/general', 'JurnalController@general');

Route::get('jurnal/kasmasuk', 'JurnalController@kasmasuk');
Route::get('jurnal/kaskeluar', 'JurnalController@kaskeluar');
Route::get('jurnal/transferkas', 'JurnalController@transferkas');


Route::get('/get-types/{group}', function ($group) {
    $types = \App\Akun::where('group', $group)
    ->distinct()
    ->pluck('type');
    return response()->json($types);
});
Route::get('/get-categories/{type}', function ($type) {
    $categories = \App\Akun::where('type', $type)
    ->distinct()
    ->pluck('category');
    return response()->json($categories);
});
Route::get('/akun/{id}/edit','AkunController@edit');

Route::delete('/akun/{id}','AkunController@destroy');
Route::get('akun/filter-parents/{category}', 'AkunController@filterParents');

Route::get('akun', 'AkunController@index');
Route::post('akun', 'AkunController@store');
Route::get('/akun/{parrent}/children', 'AkunController@getChildren');
Route::get('jurnal', 'JurnalController@jurnal');
Route::get('/jurnal/create','JurnalController@transaksi');

Route::post('jurnal/getjurnaldata', 'JurnalController@getjurnaldata');
Route::post('jurnal/getbukubesardata', 'JurnalController@getbukubesardata');
Route::post('jurnal', 'JurnalController@jurnal');
Route::get('jurnal/bukubesar', 'JurnalController@bukubesar');
Route::post('jurnal/bukubesar', 'JurnalController@bukubesar');
Route::get('jurnal/report', 'JurnalController@index');
Route::get('jurnal/rugilaba', 'JurnalController@laporanRugiLaba');
Route::post('jurnal/rugilaba', 'JurnalController@rugilaba');
Route::delete('/jurnal/{id}','JurnalController@destroy');
Route::get('jurnal/neraca', 'JurnalController@neraca');
Route::get('jurnal/neracasaldo', 'JurnalController@neracaSaldo');

Route::get('jurnal/ccreate', 'JurnalController@ccreate');
Route::get('jurnal/generaldel/{id}', 'JurnalController@generaldel');
Route::post('/jurnal/store','JurnalController@store');
Route::post('/jurnal/cupdate','JurnalController@cupdate');
Route::post('/jurnal/trxstore','JurnalController@trxstore');
Route::get('/jurnal/trxcreate','JurnalController@trxcreate');
Route::post('/jurnal/trxupdate','JurnalController@trxupdate');
Route::get('/jurnal/closed','JurnalController@closed');
Route::post('/jurnal/closed','JurnalController@closestore');
Route::post('/jurnal/closeupdate','JurnalController@closeupdate');
Route::get('/jurnal/jpenutup','JurnalController@jpenutup');
Route::post('/jurnal/penutup','JurnalController@penutup');


Route::post('/jurnal/cstore','JurnalController@cstore');
Route::get('/jurnal/cstore','JurnalController@ccreate');
Route::post('/jurnal/create','JurnalController@create');

Route::post('/distrouter/client_monitor','DistrouterController@client_monitor');

Route::get('/olt/coba','OltController@coba');
Route::post('/olt/ont_status','OltController@ont_status');
Route::post('/olt/onu_detail','OltController@onu_detail');
Route::get('/olt','OltController@index');
Route::get('/olt/getemptyonuid','OltController@getemptyonuid');
Route::get('/olt/addonu/{customerid}/{oltis}','OltController@addonu');
Route::get('/olt/addonu/{oltis}','OltController@addonucustome');
Route::POST('/olt/getolt/onu','OltController@getOltOnu');
Route::post('/olt/onuregister','OltController@configure');
Route::post('/olt/onuregistercst','OltController@configurecst');

Route::delete('/olt/delete/{oltId}/{oltPonIndex}/{onuId}','OltController@onudelete');
Route::post('/olt/reboot/{oltId}/{oltPonIndex}/{onuId}','OltController@onureboot');
Route::post('/olt/reset/{oltId}/{oltPonIndex}/{onuId}','OltController@onureset');



Route::post('/olt/table_onu_unconfig','OltController@table_onu_unconfig');

Route::get('/olt/getFreeOnuId','OltController@getFreeOnuId');

Route::get('/olt/unconfig','OltController@unconfig');
Route::post('/olt/table_olt_list','OltController@table_olt_list');
Route::get('/olt/create','OltController@create');
Route::post('/olt','OltController@store');
Route::get('/olt/{id}/edit','OltController@edit');
Route::patch('/olt/{id}','OltController@update');
Route::delete('/olt/{id}','OltController@destroy');
Route::get('/olt/getoltinfo/{id}','OltController@getOltInfo');
Route::get('/olt/getoltpon/{id}','OltController@getOltPon');
Route::get('/olt/{id}','OltController@show');




Route::post('/sale/table_sales','SaleController@table_sales');
Route::get('/sale','SaleController@index');


Route::get('/file/backup','FileController@backup');
Route::get('/file/download/{filename}', 'FileController@download')->name('file.download');
Route::delete('/file/{filename}', 'FileController@delete')->name('file.delete');
Route::post('/file','FileController@store');
Route::delete('/file/customer/{id}','FileController@destroy');

Route::post('/whatsapp/wa_ticket','WhatsappController@wa_ticket');

// Route::get('/whatsapp/qrcode','WhatsappController@scan_qrcode');
// Route::get('/whatsapp/qrcode','WhatsappController@showQr');
// Route::post('/whatsapp/logout','WhatsappController@logout');
// Route::post('/whatsapp/restart','WhatsappController@restart');
// Route::get('/whatsapp/groups','WhatsappController@getGroups');


Route::prefix('wa')->group(function () {
     // Inbound webhook dari Node
    Route::post('webhook',          [WhatsappController::class, 'webhook']);

    // Outbound: kirim pesan & terima messageId
    Route::post('{session}/send',   [WhatsappController::class, 'send']);
    
    // Ack update dari Node
    Route::post('{session}/ack',    [WhatsappController::class, 'ack']);
    Route::get('{session}/qr', [WhatsappController::class, 'showQr']);
    Route::post('{session}/send', [WhatsappController::class, 'send']);
    Route::post('{session}/logout', [WhatsappController::class, 'logout']);
    Route::post('{session}/restart', [WhatsappController::class, 'restart']);
    Route::get('{session}/groups', [WhatsappController::class, 'getGroups']);   
    Route::post('webhook', [WhatsappController::class, 'webhook']);
});

// Route::get('/wa/start', function () {
//     return view('wa.start'); // halaman input session baru
// });

Route::post('/wa/start', function (\Illuminate\Http\Request $request) {
    $session = $request->input('session');
    $response = Http::post('http://127.0.0.1:3001/api/start', [ 'session' => $session ]);
    return response()->json($response->json());
});
Route::get('/wa/dashboard', function () {
    return view('wa.dashboard');
});

Route::get('/wa/status', function () {
    $response = Http::get('http://127.0.0.1:3001/api/health');
    return response()->json($response->json());
});

Route::get('/wa/{session}/status', function ($session) {
    $response = Http::get("http://127.0.0.1:3001/api/{$session}/qr");
    return response()->json($response->json());
});
Route::get('/wa/{session}/stats', function ($session) {
    return response()->json([
        'count' => \App\Helpers\WaGatewayHelper::countSentMessagesBySession($session)
    ]);
});
Route::get('/wa/{session}/chats', [WhatsappController::class, 'chats'])->name('wa.chats');


Route::get('/wa/logs', [WhatsappController::class, 'logs']);
Route::post('/wa/logs/table', [WhatsappController::class, 'logsTable']);

Route::get('/wa/chat', [WhatsappController::class, 'chat'])->name('wa.chat');
Route::get('/wa/chat-data', [WhatsappController::class, 'chatTable'])->name('wa.chatTable');
// routes/web.php
Route::get('/wa/{session}/chats', [WhatsappController::class, 'chats'])
->name('wa.chats');


//Route::post('/wa/{session}/send', [WhatsappController::class, 'send']);

//Site

Route::get('/xx', function(){
    $config = array();
    $config['center'] = 'auto';
    $config['onboundschanged'] = 'if (!centreGot) {
        var mapCentre = map.getCenter();
        marker_0.setOptions({
            position: new google.maps.LatLng(mapCentre.lat(), mapCentre.lng())
            });
        }
        centreGot = true;';

        app('map')->initialize($config);

    // set up the marker ready for positioning
    // once we know the users location
        $marker = array();
        app('map')->add_marker($marker);

        $map = app('map')->create_map();
        echo "<html><head><script type=text/javascript>var centreGot = false;</script>".$map['js']."</head><body>".$map['html']."</body></html>";
    });

Route::get('/oltonuprofile/olt/{id}','OltonuprofileController@index');
Route::get('/oltonuprofile/create/{olt}','OltonuprofileController@create');
// Route::get('/oltonuprofile/{id}/show','OltonuprofileController@show');
// Route::get('/oltonuprofile/{id}/edit','OltonuprofileController@edit');
// Route::get('/oltonuprofile/{id}','OltonuprofileController@null');
Route::post('/oltonuprofile','OltonuprofileController@store');
Route::delete('/oltonuprofile/{id}/{olt}','OltonuprofileController@destroy');
// Route::patch('/oltonuprofile/{id}','OltonuprofileController@update');

Route::get('/oltonutype/olt/{id}','OltonutypeController@index');
Route::get('/oltonutype/create/{olt}','OltonutypeController@create');
Route::post('/oltonutype','OltonutypeController@store');
Route::delete('/oltonutype/{id}/{olt}','OltonutypeController@destroy');

Route::get('/maps','SiteController@maps');
Route::get('/site','SiteController@index');
Route::get('/site/create','SiteController@create');
Route::get('/site/{id}/show','SiteController@show');
Route::get('/site/{id}/edit','SiteController@edit');
Route::get('/site/{id}','SiteController@null');
Route::post('/site','SiteController@store');
Route::delete('/site/{id}','SiteController@destroy');
Route::patch('/site/{id}','SiteController@update');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('/jobschedule-ajax', [\App\Http\Controllers\HomeController::class, 'jobScheduleAjax'])->name('jobschedule.ajax');
