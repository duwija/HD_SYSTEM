@extends('layout.main')
@section('title','Akun List')
@section('content')
<section class="content-header">

  <div class="card card-primary card-outline">
    <div class="card-header">
      <h3 class="card-title">Akun List  </h3>
      <button type="button" class="float-right btn bg-primary btn-sm" data-toggle="modal" data-target="#modal-akun"> Add New Akun </button>
    </div>

    <!-- /.card-header -->
    <div class="card-body">
      <table id="akun-table" class="table table-bordered table-striped">
        <thead>
          <tr>
            <!-- <th>#</th> -->
            <th>Kode Akun</th>
            <th>Nama</th>
            <th>Grup</th>
            <th>Kategori</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <!-- @php $counter=1 @endphp -->
          @foreach ($rootAkuns as $akun)
          @include('akun.akun-row', ['akun' => $akun, 'level' => 0])
          @endforeach
        </tbody>
      </table>
    </div>
  </div>



  <div class="modal fade" id="modal-akun">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-body">
         <form role="form" method="POST" action="/akun">
          @csrf
          <div class="card-body">

            <!-- Input Nama -->
            <div class="form-group">
              <label for="name">Name</label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" id="name" placeholder="Akun Name" value="{{ old('name') }}">
              @error('name')
              <div class="error invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Input Kode Akun -->
            
            <!-- Dropdown Category -->
            <div class="form-group">
              <label for="category">Category</label>
              <select name="category" id="category" class="form-control" onchange="filterParentAccounts(); updateHiddenInput();">
                <option >==Choose Category==</option>
                <optgroup label="aktiva">
                  <option value="kas & bank" data-kode="1-10000">Kas & Bank</option>
                  <option value="akun piutang" data-kode="1-10100">Akun Piutang</option>
                  <option value="persediaan" data-kode="1-10200">Persediaan</option>
                  <option value="aktiva lancar lainnya" data-kode="1-10300">Aktiva Lancar Lainnya</option>
                  <option value="aktiva tetap" data-kode="1-10700">Aktiva Tetap</option>
                  <option value="depresiasi dan amortisasi" data-kode="1-10750">Depresiasi & Amortisasi</option>
                  <option value="aktiva lainnya" data-kode="1-10780">Aktiva Lainnya</option>
                </optgroup>
                <optgroup label="kewajiban">
                  <option value="akun hutang" data-kode="2-20100">Akun Hutang</option>
                  <option value="kewajiban lancar lainnya" data-kode="2-20200">Kewajiban Lancar Lainnya</option>
                  <option value="kewajiban jangka panjang" data-kode="2-20700">Kewajiban Jangka Panjang</option>
                </optgroup>
                <optgroup label="ekuitas">
                  <option value="ekuitas" data-kode="3-30000">Ekuitas</option>
                </optgroup>
                <optgroup label="pendapatan">
                  <option value="pendapatan" data-kode="4-40000">Pendapatan</option>
                  <option value="pendapatan lainnya" data-kode="7-70000">Pendapatan Lainnya</option>
                </optgroup>
                <optgroup label="beban">
                  <option value="harga pokok penjualan" data-kode="5-50000">Harga Pokok Penjualan</option>
                  <option value="beban" data-kode="6-60000">Beban</option>
                  <option value="beban lainnya" data-kode="8-80000">Beban Lainnya</option>
                </optgroup>
              </select>
            </div>
            <div class="form-group">
              <label for="akun_code">Kode Akun</label>
              <input type="text" class="form-control @error('akun_code') is-invalid @enderror" name="akun_code" id="akun_code" placeholder="Kode Akun" value="{{ old('akun_code') }}">
              @error('akun_code')
              <div class="error invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <!-- Group (Disabled Input) -->
            <div class="form-group">
              <label for="group">Group</label>
              <input class="form-control @error('group') is-invalid @enderror" type="text"  readonly name="group" id="group" value="">
              @error('group')
              <div class="error invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="form-group">
              <label for="parent">Parent Akun</label>
              <select name="parent" id="parent" class="form-control">
                <option value="">None</option>
             <!--  @foreach ($parents as $parent)
              <option value="{{ $parent->akun_code }}">{{ $parent->name }} ({{ $parent->akun_code }})</option>
              @endforeach -->
            </select>
          </div>

          <div class="row">
            <div class="form-group col-md-2">
              <label for="tax">Tax Akun ?</label>
              <select disabled name="tax" id="tax" class="form-control" onchange="toggleTaxValue()">
                <option value="0">No</option>
                <option value="1">Yes</option>
              </select>
            </div>

            <div class="form-group col-md-2">
              <label for="tax_value">Tax Amount</label>
              <input class="form-control @error('tax_value') is-invalid @enderror" type="number" name="tax_value" id="tax_value" value="0" disabled>
              @error('tax_value')
              <div class="error invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          <!-- Hidden Created At -->
          <input type="hidden" name="created_at" value="{{ now() }}">

        </div>

        <!-- Footer Submit -->
        <div class="modal-footer justify-content-between">
          <button type="submit" class="btn btn-primary">Submit</button>
        </div>
      </form>

    </div>

  </div>
</div>
</div>

</section>

@endsection
@section('footer-scripts')
<script>
  function toggleTaxValue() {
    const taxDropdown = document.getElementById("tax");
    const taxValueInput = document.getElementById("tax_value");

        // Periksa apakah opsi Yes dipilih
    if (taxDropdown.value === "1") {
            taxValueInput.disabled = false; // Aktifkan input
            taxValueInput.value = ""; // Kosongkan nilai untuk input
          } else {
            taxValueInput.disabled = true; // Nonaktifkan input
            taxValueInput.value = "0"; // Set nilai default
          }
        }

        function updateHiddenInput() {
          const dropdown = document.getElementById("category");
          const selectedOption = dropdown.options[dropdown.selectedIndex];
      const groupLabel = selectedOption.parentNode.label; // Ambil label dari optgroup
      const kodeAkun = selectedOption.getAttribute("data-kode"); 
      const taxValueInput = document.getElementById("tax_value");

      // Gabungkan group dan option
      const group = groupLabel;

      // Set value ke hidden input
      document.getElementById("group").value = group;
      document.getElementById("akun_code").value = kodeAkun;
      const taxinput = document.getElementById("tax");
      if (group === "kewajiban") {
            taxinput.disabled = false; // Aktifkan input

          } else {
            taxinput.disabled = true; // Nonaktifkan input
            taxValueInput.disabled = true; // Nonaktifkan input

          }
        }
      </script>
     <!--  <script>
        document.getElementById('parent').addEventListener('change', function () {
          const parentCode = this.value;
          const childDropdown = document.getElementById('child');

          if (parentCode) {
        fetch(`/akun/${parentCode}/children`) // Endpoint untuk mendapatkan child akun
        .then(response => response.json())
        .then(data => {
          childDropdown.innerHTML = '<option value="">-- Pilih Child Akun --</option>';
          data.forEach(child => {
            childDropdown.innerHTML += `<option value="${child.akun_code}">${child.name} (${child.akun_code})</option>`;
          });
          childDropdown.disabled = false;
        });
      } else {
        childDropdown.innerHTML = '<option value="">-- Pilih Child Akun --</option>';
        childDropdown.disabled = true;
      }
    });

  </script> -->
  <script>
   function filterParentAccounts() {
    const selectedCategory = document.getElementById('category').value;
    const parentDropdown = document.getElementById('parent');

    if (selectedCategory) {
      fetch(`/akun/filter-parents/${selectedCategory}`)
      .then(response => response.json())
      .then(data => {
                // Clear existing options
        parentDropdown.innerHTML = '<option value="">None</option>';

                // Populate with new options
        data.forEach(account => {
          const option = document.createElement('option');
          option.value = account.akun_code;
          option.textContent = `${account.name} (${account.akun_code})`;
          parentDropdown.appendChild(option);
        });
      })
      .catch(error => console.error('Error fetching parent accounts:', error));
    } else {
      parentDropdown.innerHTML = '<option value="">None</option>';

    }
  }

</script>


@endsection