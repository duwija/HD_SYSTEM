<!DOCTYPE html>
<html>
<head>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
</head>
<body><div class="container" style="border: 1px solid #5DADE2;border-radius: 5px; margin-top: 20px; margin-bottom: 20px  ">
  <div>
   <table style="border: none; width: 100%">
    <tr style="border: none">
      <td align="left" colspan="3">
        <img width="100px" src="../favicon.png">
      </td>
      <td align="right">
       <a href="/" class="btn btn-primary">HOME</a>
     </tr>

   </table> 
   
 </br>
</div>
<div class="card card-primary card-outline">
  <div style="background-color: #5DADE2; padding: 10px;  margin: -15px" class="card-header">


   <form role="form" method="post" action="/ticket/reportsrc">
    @csrf
    
    <table style="color: white;">
      <tr>
        <td style="padding: 10px">
          Show From 
        </td>
        <td>

          <div  style="padding-right: 5px" class="input-group  date" id="reservationdate" data-target-input="nearest">
            <input type="text" name="date_from" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{$date_from}}" />
            <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
            </div>
          </div>
        </td>
        <td style="padding: 10px">
         To 
       </td>
       <td>


        <div class="input-group date" id="reservationdate" data-target-input="nearest">
          <input type="text" name="date_end" id="date" class="form-control datetimepicker-input" data-target="#reservationdate" value="{{$date_end}}" />
          <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
          </div>
        </div>

      </td>
      <td><div class="input-group p-1 col-md-3">
       <button style="margin-left: 5px" type="submit" class="btn btn-warning">show</button>
     </div> 
   </td>

 </tr>
</table>

</form>
</div>
</div>
<hr>

<h3>Chart total tiket by Category </h3>

<canvas id="myChart" style="width:100%;"></canvas>
</br>

<h3>Chart total tiket by Date </h3>

<canvas id="myChartDate" style="width:100%;"></canvas>
</br>

<hr>
<h3>Top 10 Customer's Ticket</h3>
</hr>      
<table class="table table-hover">
  <thead>
    <tr>
      <th>Rank</th>
      <th>Customer Name</th>
      <th>Total Ticket</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    @foreach( $ticket_customer as $ticket_customer)
    <tr>
      <th scope="row">{{ $loop->iteration }}</th>
      <td>{{ $ticket_customer->name }}</td>
      <td>{{ $ticket_customer->count }}</td>

      <td >
        <div class="float-right " >

          <a href="/ticket/view/{{ $ticket_customer->cust_id }}" class="btn btn-primary btn-sm "> Show Tickets </a>


        </div>
      </td>

    </tr>
    @endforeach
  </tbody>
</table>
</div>

</body>
<?php
$count ="";
$name ="";
foreach( $ticket_report as $ticket_report)
{
  if (!empty($count))
  {
    $count  = $count.", ". $ticket_report->count; 
    $name  = $name.',"'.$ticket_report->name.'"';
  }
  else
  {
    $count = $ticket_report->count; 
    $name = '"'.$ticket_report->name.'"';
  }



}




?>
<script>
  var xValues = [<?php 

    echo $name;


    ?>];
  var yValues = [<?php 

    echo $count;


    ?>];

  var barColors = ["red", "green","blue","orange","brown"];

  new Chart("myChart", {
    type: "bar",
    data: {
      labels: xValues,
      datasets: [{
        backgroundColor: "#78c8c0",
        data: yValues
      }]
    },
    options: {
      scales: {
        yAxes: [{
          ticks: {
            beginAtZero: true
          }
        }]
      },
      legend: {display: false},
      title: {
        display: true,
        text: "Tiket Base on Category"
      }
    }
  });
</script>




<?php
$countx ="";
$date ="";
foreach( $ticket_date as $ticket_date)
{
  if (!empty($countx))
  {
    $countx  = $countx.", ". $ticket_date->countdate; 
    $date  = $date.',"'.$ticket_date->date.'"';

  }
  else
  {
    $countx = $ticket_date->countdate; 
    $date = '"'.$ticket_date->date.'"';

  }



}



?>


<script>
  var xValuesx = [<?php 

    echo $date;


    ?>];
  var yValuesx = [<?php 

    echo $countx;


    ?>];



  new Chart("myChartDate", {
    type: "bar",
    data: {
      labels: xValuesx,
      datasets: [{

        backgroundColor: "#3e95cd",

        data: yValuesx
      }]
    },
    options:{
     scales: {
      yAxes: [{
        ticks: {
          beginAtZero: true
        }
      }]
    },
    legend: {display: false},
    title: {
      display: true,
      text: "Ticket Base on Date"
    }
  }
});
</script>
</body>
</html>
