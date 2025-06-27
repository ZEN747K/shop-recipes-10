<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ESC/POS Printer</title>
    <style>
        body {
          font-family: Arial, sans-serif;
          padding: 20px;
        }

        button {
          padding: 10px 16px;
          margin: 8px 0;
          font-size: 16px;
          width: 100%;
        }

        textarea {
          width: 100%;
          height: 100px;
          margin: 8px 0;
        }
    </style>
</head>
<body>
<button onclick="sendCommand('STATUS_PRINTER')">Check Printer Status</button>
<button onclick="sendPrintCommand()">Print Receipt</button>
<pre id="statusOutput" style="margin-top: 20px;"></pre>
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
