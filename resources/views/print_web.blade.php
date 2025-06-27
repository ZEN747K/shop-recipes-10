<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ESC/POS Printer</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px 0;
            color: #2d2d2d;
            background: #ffffff;
        }

        button {
            padding: 10px 16px;
            margin: 8px 0;
            font-size: 16px;
            width: 100%;
        }

        .receipt {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 30px;
            border-radius: 5px;
        }

        .receipt h2 {
            text-align: center;
            margin-top: 5px;
            margin-bottom: 20px;
            font-weight: 600;
            color: #1e293b;
        }

        .receipt span {
            font-weight: 700;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 1px;
        }

        .header .info,
        .header .detail {
            display: table-cell;
            vertical-align: top;
        }

        .header .info {
            text-align: left;
        }

        .header .detail {
            text-align: right;
        }

        .info p,
        .detail p {
            margin: 4px 0;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            padding: 10px;
            font-size: 14px;
            border-bottom: 1px solid #e2e8f0;
        }

        th:nth-child(1),
        td:nth-child(1) {
            text-align: left;
            width: 60%;
        }

        th:nth-child(2),
        td:nth-child(2) {
            text-align: center;
            width: 10%;
        }

        th:nth-child(3),
        td:nth-child(3) {
            text-align: right;
            width: 30%;
        }

        .total {
            text-align: right;
            font-weight: 700;
            color: #1e293b;
            border-top: 2px solid #000;
            margin-top: 20px;
            padding-top: 12px;
            font-size: 16px;
        }
    </style>
</head>
<body>
<button onclick="sendCommand('STATUS_PRINTER')">Check Printer Status</button>
<button onclick="sendPrintCommand()">Print Receipt</button>
<pre id="statusOutput" style="margin-top: 20px;"></pre>
<div id="print-area">
    <div class="receipt">
        <h2><span>{{ $config->name }}</span></h2>
        <div class="header">
            <div class="info">
                <p><strong>เลขที่ใบเสร็จ #{{ $pay->payment_number }}</strong></p>
                <p>วันที่: {{ $pay->created_at }}</p>
            </div>
            @if(!empty($get))
            <div class="detail">
                <p><strong>ชื่อ: {{ $get['name'] }}</strong></p>
                <p>เบอร์โทรศัพท์: {{ $get['tel'] }}</p>
                <p>เลขประจำตัวผู้เสียภาษี: {{ $get['tax_id'] }}</p>
                <p>ที่อยู่: {{ $get['address'] }}</p>
            </div>
            @endif
        </div>
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($order as $rs)
                <tr>
                    <td>
                        <div>{{ $rs['menu']->name }}</div>
                        @foreach($rs['option'] as $option)
                        <div style="font-size: 12px; color: #6b7280;">+ {{ $option['option']->type }}</div>
                        @endforeach
                    </td>
                    <td>{{ $rs->quantity }}</td>
                    <td>{{ number_format($rs->price, 2) }} ฿</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <p class="total">Total: {{ number_format($pay->total, 2) }} ฿</p>
    </div>
</div>
<script>
    function getBridge() {
      if (window.posRegisterInterface) return window.posRegisterInterface;
      if (window.webkit?.messageHandlers?.posRegisterInterface) return window.webkit.messageHandlers.posRegisterInterface;
      return null;
    }

    function sendCommand(command) {
      const payload = {
        command: command,
        payload: []
      };
      const bridge = getBridge();
      if (bridge) {
        if (bridge.postMessage) bridge.postMessage(JSON.stringify(payload));
        else if (typeof bridge.sendRequest === "function") bridge.sendRequest(JSON.stringify(payload));
      } else {
        alert("JSBridge not available");
      }
    }

    function sendPrintCommand() {
      const payload = {
        "command": "PRINT_START",
        "payload": [
          {
            "align": "center",
            "bold": true,
            "data": "{{ $config->name }}",
            "size": 2,
            "type": "text"
          },
          {
            "type": "newline"
          },
          {
            "align": "left",
            "bold": true,
            "data": "เลขที่ใบเสร็จ #{{ $pay->payment_number }}",
            "type": "text"
          },
          {
            "align": "left",
            "data": "วันที่: {{ $pay->created_at }}",
            "type": "text"
          },
          {
            "type": "newline"
          },
          {
            "type": "line"
          },
          @foreach($order as $rs)
          {
            "columns": [
              {
                "text": "{{ $rs['menu']->name }}",
                "width": 60
              },
              {
                "text": "{{ $rs->quantity }}",
                "width": 10
              },
              {
                "text": "{{ number_format($rs->price, 2) }} ฿",
                "width": 30
              }
            ],
            "type": "table"
          },
          @foreach($rs['option'] as $option)
          {
            "align": "left",
            "data": "+ {{ $option['option']->type }}",
            "type": "text"
          },
          @endforeach
          {
            "type": "line"
          },
          @endforeach
          {
            "type": "newline"
          },
          {
            "bold": true,
            "size": "2",
            "type": "line"
          },
          {
            "align": "right",
            "bold": true,
            "data": "Total: {{ number_format($pay->total, 2) }} ฿",
            "size": "1",
            "type": "text"
          },
           {
            "type": "newline"
          },
          {
            "type": "newline"
          }
        ]
      }
      const bridge = getBridge();
      if (bridge) {
        if (bridge.postMessage) bridge.postMessage(JSON.stringify(payload));
        else if (typeof bridge.sendRequest === "function") bridge.sendRequest(JSON.stringify(payload));
      } else {
        alert("JSBridge not available");
      }
    }

    function onPrinterStatusUpdate(connected) {
      const msg = connected ? "🟢 Printer Connected" : "🔴 Printer Not Connected";
      document.getElementById("statusOutput").textContent = msg;
    }
</script>
</body>
</html>
