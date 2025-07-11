@foreach($tickets as $t)
@php
switch ($t->status) {
  case 'Open':     $color = 'bg-danger'; break;
  case 'Close':    $color = 'bg-secondary'; break;
  case 'Pending':  $color = 'bg-warning'; break;
  case 'Solve':    $color = 'bg-info'; break;
  default:         $color = 'bg-primary'; break;
}
@endphp
<div>
  <div class="timeline-item m-1 row pt-2 col-12 col-sm-6 col-md-12 col-lg-8">
    <div class="col-md-12">
      <span class="time p-1"><i class="fas fa-clock"></i> {{ date('H:i', strtotime($t->time)) }}</span>
      <strong><i class="fas fa-user-friends pl-4 pr-lg-1"></i> {{ $t->user->name ?? '-' }}</strong> | {{ $t->member ?? '-' }}  #Created at : {{ $t->created_at }}
      <hr class="bg-info">
    </div>
    <div class="col-md-7">
      <span class="timeline-header">
        Ticket ID : <a href="/ticket/{{ $t->id }}"><button class="btn {{ $color }} btn-sm">{{ $t->id }}</button></a> <br>
        CID/Customer : 
        @if($t->customer)
        <a href="/customer/{{ $t->customer->id }}">{{ $t->customer->customer_id }} ({{ $t->customer->name }})</a>
        @else
        -
        @endif
      </span>
    </div>
    <div class="col-md-5">
      <span class="pr-lg-5">Report by : {{ $t->called_by }} | {{ $t->phone }}<br>Created by : {{ $t->create_by }}</span>
    </div>
    <div class="timeline-body">
      <div class="ribbon-wrapper">
        <div class="ribbon {{ $color }}">
          {{ $t->status }}
        </div>
      </div>
      <strong>{{ $t->tittle }}</strong>
    </div>
  </div>
</div>
@endforeach
