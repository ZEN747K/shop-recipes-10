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
      const items = @json($order->map(function($rs){
            $opts = [];
            foreach($rs['option'] as $op){
                $opts[] = $op['option']->type;
            }
            return [
                'name' => $rs['menu']->name,
                'qty' => $rs->quantity,
                'price' => number_format($rs->price,2),
                'options' => $opts
            ];
        }));

      const lines = [
        {"align":"center","bold":true,"data":"{{$config->name}}","size":2,"type":"text"},
        {"type":"newline"},
        {"align":"left","bold":true,"data":"เลขที่ใบเสร็จ #{{$pay->payment_number}}","type":"text"},
        {"align":"left","data":"วันที่: {{$pay->created_at}}","type":"text"},
        {"type":"newline"},
        {"type":"line"}
      ];
      items.forEach(item => {
        lines.push({"columns":[{"text":item.name,"width":60},{"text":String(item.qty),"width":10},{"text":item.price+" \u0e3f","width":30}],"type":"table"});
        item.options.forEach(op=>{
          lines.push({"align":"left","data":"+ "+op,"type":"text"});
        });
        lines.push({"type":"line"});
      });
      lines.push({"type":"newline"});
      lines.push({"bold":true,"size":"2","type":"line"});
      lines.push({"align":"right","bold":true,"data":"Total: {{number_format($pay->total,2)}} \u0e3f","size":"1","type":"text"});
      lines.push({"type":"newline"});
      lines.push({"type":"newline"});

      const payload = {"command":"PRINT_START","payload": lines};
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
