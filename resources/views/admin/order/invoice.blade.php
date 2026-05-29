<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - {{ $order->invoice_no ?? $order->order_id }}</title>

<style>
    * {
        box-sizing: border-box;
    }

    @page {
        margin: 30px 35px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 12px;
        color: #111;
        margin: 0;
        padding: 20px 25px;
    }

    .container {
        width: 100%;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        border: 1px solid #d0d0d0;
        padding: 8px 10px;
        vertical-align: top;
        word-wrap: break-word;
    }

    .border-none td {
        border: none !important;
        padding: 4px 0;
    }

    .heading {
        background: #f0f0f0;
        font-weight: bold;
    }

    .text-right {
        text-align: right;
    }

    .text-center {
        text-align: center;
    }

    .fw-bold {
        font-weight: bold;
    }

    .small {
        font-size: 11px;
    }

    .invoice-title {
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 8px;
        letter-spacing: 1px;
    }

    .company-logo {
        width: 160px;
        margin-bottom: 10px;
    }

    .mt-10 { margin-top: 10px; }
    .mt-20 { margin-top: 20px; }
    .mt-30 { margin-top: 30px; }

    /* Header section */
    .header-left {
        padding: 12px 14px 12px 0;
        line-height: 20px;
    }

    .header-right {
        padding: 12px 0 12px 14px;
        line-height: 22px;
    }

    /* Billing table */
    .billing-table td {
        padding: 10px 12px;
        line-height: 19px;
    }

    /* Product table */
    .product-table th {
        padding: 9px 10px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .3px;
        background: #f0f0f0;
    }

    .product-table td {
        padding: 9px 10px;
    }

    .product-name {
        font-size: 11px;
        line-height: 17px;
    }

    /* Totals table */
    .totals-table td {
        padding: 8px 12px;
    }

    .totals-table .grand-total td {
        background: #f5f5f5;
        font-weight: bold;
        font-size: 13px;
        padding: 10px 12px;
    }

    /* Divider */
    .section-divider {
        border: none;
        border-top: 1px solid #ddd;
        margin: 18px 0;
    }

    /* Signature */
    .signature-block {
        margin-top: 45px;
        text-align: right;
        padding-right: 10px;
        line-height: 20px;
    }

    .signature-line {
        border-top: 1px solid #555;
        display: inline-block;
        width: 180px;
        margin-top: 40px;
        padding-top: 5px;
        text-align: center;
        font-size: 11px;
    }

    /* Footer */
    .footer {
        margin-top: 28px;
        text-align: center;
        font-size: 11px;
        color: #555;
        border-top: 1px solid #ddd;
        padding-top: 12px;
        padding-bottom: 6px;
    }
</style>

</head>
<body>

<div class="container">

    {{-- HEADER --}}
    <table class="border-none">
        <tr>
            <td width="55%" class="header-left">
                <img src="{{ public_path('backend/assets/images/logo.png') }}"
                     class="company-logo">
                <div class="mt-10">
                    <strong>Your Company Pvt Ltd</strong><br>
                    Mumbai, Maharashtra - 400001<br>
                    GSTIN : 27ABCDE1234F1Z5<br>
                    PAN : ABCDE1234F<br>
                    Phone : +91 9876543210<br>
                    Email : support@company.com
                </div>
            </td>

            <td width="45%" class="text-right header-right">
                <div class="invoice-title">TAX INVOICE</div>
                <table class="border-none" style="margin-left:auto;width:auto">
                    <tr>
                        <td style="text-align:right;padding:2px 6px 2px 0;color:#555;font-size:11px">Invoice No</td>
                        <td style="text-align:right;padding:2px 0;font-weight:bold">
                            {{ $order->invoice_no ?? 'INV-'.$order->id }}
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:right;padding:2px 6px 2px 0;color:#555;font-size:11px">Order ID</td>
                        <td style="text-align:right;padding:2px 0;font-weight:bold">{{ $order->order_id }}</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;padding:2px 6px 2px 0;color:#555;font-size:11px">Date</td>
                        <td style="text-align:right;padding:2px 0">{{ $order->created_at->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;padding:2px 6px 2px 0;color:#555;font-size:11px">Payment</td>
                        <td style="text-align:right;padding:2px 0">{{ strtoupper($order->payment_method) }}</td>
                    </tr>
                    <tr>
                        <td style="text-align:right;padding:2px 6px 2px 0;color:#555;font-size:11px">Status</td>
                        <td style="text-align:right;padding:2px 0">{{ ucfirst($order->payment_status) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- BILLING + SHIPPING --}}
    <table class="mt-20 billing-table">
        <tr class="heading">
            <td width="50%" style="padding:8px 12px">Billing Details</td>
            <td width="50%" style="padding:8px 12px">Shipping Address</td>
        </tr>
        <tr>
            <td>
                <strong>{{ $order->user->name ?? '-' }}</strong><br>
                {{ $order->user->email ?? '-' }}<br>
                {{ $order->user->phone ?? '-' }}
            </td>
            <td>
                @if($order->shippingAddress)
                    <strong>{{ $order->shippingAddress->name }}</strong><br>
                    {{ $order->shippingAddress->address }}<br>
                    @if($order->shippingAddress->landmark)
                        Landmark : {{ $order->shippingAddress->landmark }}<br>
                    @endif
                    {{ $order->shippingAddress->city }},
                    {{ $order->shippingAddress->state }}
                    - {{ $order->shippingAddress->pincode }}<br>
                    {{ $order->shippingAddress->country }}<br>
                    Phone : {{ $order->shippingAddress->phone }}
                @else
                    No shipping address found
                @endif
            </td>
        </tr>
    </table>

    {{-- PRODUCT TABLE --}}
    <table class="mt-20 product-table">
        <thead>
            <tr class="heading">
                <th width="5%"  class="text-center">#</th>
                <th width="39%">Product</th>
                <th width="10%" class="text-center">HSN</th>
                <th width="13%" class="text-right">Price</th>
                <th width="8%"  class="text-center">Qty</th>
                <th width="10%" class="text-center">GST</th>
                <th width="15%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td class="product-name">
                        <strong>{{ $item->product->name ?? 'Deleted Product' }}</strong>
                    </td>
                    <td class="text-center">8436</td>
                    <td class="text-right">₹{{ number_format($item->price, 2) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">{{ $order->gst_rate }}%</td>
                    <td class="text-right">₹{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALS --}}
    <table class="mt-20 totals-table">
        <tr>
            <td width="65%" rowspan="8" style="padding:12px 14px;vertical-align:top">

                <strong>Amount In Words</strong><br><br>
                <span style="font-size:12px">
                    INR {{ number_format($order->grand_total, 2) }} Rupees Only
                </span>

                <div class="mt-20 small" style="line-height:19px">
                    <strong>Terms &amp; Conditions</strong><br><br>
                    1. Goods once sold will not be taken back.<br>
                    2. Subject to Mumbai jurisdiction only.<br>
                    3. This is a computer generated invoice.<br>
                    4. Warranty subject to manufacturer terms.
                </div>

            </td>
            <td width="20%" style="color:#555">Subtotal</td>
            <td width="15%" class="text-right">₹{{ number_format($order->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td style="color:#555">Discount</td>
            <td class="text-right" style="color:#c00">− ₹{{ number_format($order->discount, 2) }}</td>
        </tr>
        <tr>
            <td style="color:#555">Shipping</td>
            <td class="text-right">₹{{ number_format($order->shipping_charge, 2) }}</td>
        </tr>

        @if($order->is_interstate)
            <tr>
                <td style="color:#555">IGST ({{ $order->gst_rate }}%)</td>
                <td class="text-right">₹{{ number_format($order->igst_amount, 2) }}</td>
            </tr>
        @else
            <tr>
                <td style="color:#555">CGST ({{ $order->gst_rate / 2 }}%)</td>
                <td class="text-right">₹{{ number_format($order->cgst_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="color:#555">SGST ({{ $order->gst_rate / 2 }}%)</td>
                <td class="text-right">₹{{ number_format($order->sgst_amount, 2) }}</td>
            </tr>
        @endif

        <tr class="grand-total">
            <td class="fw-bold" style="background:#f0f0f0">Grand Total</td>
            <td class="text-right fw-bold" style="background:#f0f0f0;font-size:13px">
                ₹{{ number_format($order->grand_total, 2) }}
            </td>
        </tr>
        <tr>
            <td style="color:#555">Paid Amount</td>
            <td class="text-right" style="color:#1a7431">₹{{ number_format($order->paid_amount, 2) }}</td>
        </tr>
        <tr>
            <td style="color:#555">Due Amount</td>
            <td class="text-right" style="color:#c00">₹{{ number_format($order->due_amount, 2) }}</td>
        </tr>
    </table>

    {{-- SIGNATURE --}}
    <div class="signature-block">
        <strong>For Your Company Pvt Ltd</strong><br>
        <div class="signature-line">Authorised Signatory</div>
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        Thank you for your business! &nbsp;|&nbsp; support@company.com &nbsp;|&nbsp; +91 9876543210
    </div>

</div>

</body>
</html>