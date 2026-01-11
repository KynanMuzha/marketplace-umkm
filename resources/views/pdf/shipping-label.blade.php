<!DOCTYPE html>
<html>
<head>
<style>
  @page {
      margin: 0;
    }

  body {
    margin: 0;
    padding: 0;
    font-size: 10px;
    color: #000;
    font-family: 'Courier New', monospace;
  }

  .wrapper {
      width: 226px;
      margin: 0 auto;
      padding-top: 14px
    }

  .label {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
  }

  .center {
    text-align: center;
  }

  .logo img {
    height: 30px;
    margin-bottom: 4px;
  }

  .store-name {
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 6px;
  }

  .section {
    margin-bottom: 6px;
  }

  .title {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
  }

  .content {
    font-size: 10px;
    font-weight: 500;
  }

  .divider {
    border-top: 1px dashed #000;
    margin: 6px 0;
  }

  .barcode {
    text-align: center;
    margin: 6px 0;
  }

  .barcode img {
    width: 100%;
    height: auto;
  }

  .resi-text {
    font-size: 9px;
    letter-spacing: 1px;
    margin-top: 2px;
  }

  .product {
    display: flex;
    justify-content: space-between;
    font-size: 9.5px;
  }
</style>
</head>

<body>
<div class="wrapper">
<div class="label">

  <!-- HEADER -->
  <div class="center logo">
    <img src="{{ public_path('logo.png') }}">
    <div class="store-name">PasarDesa</div>
  </div>

  <div class="section">
    <div class="title">Nama Toko (Pengirim)</div>
    <div class="content">{{ $order->items->first()->product->user->name ?? '-' }}</div>
  </div>

  <div class="divider"></div>

  <!-- RESI -->
  <div class="section">
    <div class="title">Nomor Resi</div>
  </div>

  <div class="barcode">
    <img src="data:image/png;base64,{{ $barcode }}">
    <div class="resi-text">{{ $trackingNumber }}</div>
  </div>

  <div class="divider"></div>

  <!-- PENERIMA -->
  <div class="section">
    <div class="title">Penerima</div>
    <div class="content">
      {{ $order->customer_name }}<br>
      {{ $order->customer_phone }}<br>
      {{ $order->customer_address }}
    </div>
  </div>

  <div class="divider"></div>

  <!-- PRODUK -->
  <div class="section">
    <div class="title">Produk</div>
    @foreach($order->items as $item)
      <div class="product">
        <span>{{ $item->product->name }} x{{ $item->quantity }}</span><br>
        <span>Rp {{ number_format($item->price,0,',','.') }}</span>
      </div>
    @endforeach
  </div>

  <div class="divider"></div>

  <!-- PAYMENT -->
  <div class="section">
    <div class="title">Metode Pembayaran</div>
    <div class="content">{{ strtoupper($order->payment_method ?? 'COD') }}</div>
  </div>
</div>
</div>
</body>
</html>
