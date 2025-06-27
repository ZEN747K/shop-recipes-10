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

        .receipt-preview {
          background: #f8f8f8;
          border: 1px solid #ccc;
          padding: 16px;
          margin: 16px 0;
          font-size: 15px;
        }
    </style>
</head>
<body>
<button onclick="sendCommand('STATUS_PRINTER')">Check Printer Status</button>
<button onclick="sendPrintCommand()">Print Receipt</button>
<div class="receipt-preview" id="receiptPreview"></div>
<pre id="statusOutput" style="margin-top: 20px;"></pre>
<script>
    // รับ jsonData จาก blade
    const jsonData = @json($jsonData ?? '{}');
    let data = {};
    try { data = typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData; } catch(e) { data = {}; }

    // ฟังก์ชันแสดงตัวอย่างใบเสร็จ/ใบกำกับภาษี
    function renderReceiptPreview() {
      if (!data || !data.pay || !data.order) return;
      let html = '';
      html += `<div style='text-align:center;font-weight:bold;font-size:18px;'>${data.config?.name || ''}</div>`;
      html += `<div>เลขที่ใบเสร็จ #${data.pay.payment_number || ''}</div>`;
      html += `<div>วันที่: ${data.pay.created_at || ''}</div>`;
      if (data.tax_full) {
        html += `<div style='margin-top:8px;'><b>ชื่อลูกค้า:</b> ${data.tax_full.name || ''}</div>`;
        html += `<div><b>เบอร์โทรศัพท์:</b> ${data.tax_full.tel || ''}</div>`;
        html += `<div><b>เลขประจำตัวผู้เสียภาษี:</b> ${data.tax_full.tax_id || ''}</div>`;
        html += `<div><b>ที่อยู่:</b> ${data.tax_full.address || ''}</div>`;
      }
      html += `<hr/>`;
      html += `<table style='width:100%;font-size:15px;'>`;
      html += `<tr><th style='text-align:left'>เมนู</th><th>จำนวน</th><th style='text-align:right'>ราคา</th></tr>`;
      data.order.forEach(rs => {
        html += `<tr><td>${rs.menu?.name || ''}</td><td style='text-align:center'>${rs.quantity}</td><td style='text-align:right'>${Number(rs.price).toFixed(2)} ฿</td></tr>`;
        if (rs.option && Array.isArray(rs.option)) {
          rs.option.forEach(opt => {
            html += `<tr><td colspan='3' style='font-size:13px;color:#666;padding-left:16px;'>+ ${opt.option?.type || ''}</td></tr>`;
          });
        }
      });
      html += `</table><hr/>`;
      html += `<div style='text-align:right;font-weight:bold;'>Total: ${Number(data.pay.total).toFixed(2)} ฿</div>`;
      document.getElementById('receiptPreview').innerHTML = html;
    }
    renderReceiptPreview();

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
      if (!data || !data.pay || !data.order) return;
      const payloadArr = [
        {
          align: "center",
          bold: true,
          data: data.config?.name || '',
          size: 2,
          type: "text"
        },
        { type: "newline" },
        {
          align: "left",
          bold: true,
          data: `เลขที่ใบเสร็จ #${data.pay.payment_number || ''}`,
          type: "text"
        },
        {
          align: "left",
          data: `วันที่: ${data.pay.created_at || ''}`,
          type: "text"
        }
      ];
      // ถ้ามี tax_full ให้แสดงข้อมูลลูกค้า
      if (data.tax_full) {
        payloadArr.push(
          { align: "left", data: `ชื่อลูกค้า: ${data.tax_full.name || ''}`, type: "text" },
          { align: "left", data: `เบอร์โทรศัพท์: ${data.tax_full.tel || ''}`, type: "text" },
          { align: "left", data: `เลขประจำตัวผู้เสียภาษี: ${data.tax_full.tax_id || ''}`, type: "text" },
          { align: "left", data: `ที่อยู่: ${data.tax_full.address || ''}`, type: "text" }
        );
      }
      payloadArr.push(
        { type: "newline" },
        { type: "line" },
        ...data.order.flatMap(rs => {
          let arr = [
            {
              columns: [
                { text: rs.menu?.name || '', width: 60 },
                { text: String(rs.quantity), width: 10 },
                { text: `${Number(rs.price).toFixed(2)} ฿`, width: 30 }
              ],
              type: "table"
            }
          ];
          if (rs.option && Array.isArray(rs.option)) {
            arr = arr.concat(rs.option.map(opt => ({
              align: "left",
              data: `+ ${opt.option?.type || ''}`,
              type: "text"
            })));
          }
          arr.push({ type: "line" });
          return arr;
        }),
        { type: "newline" },
        { bold: true, size: 2, type: "line" },
        {
          align: "right",
          bold: true,
          data: `Total: ${Number(data.pay.total).toFixed(2)} ฿`,
          size: 1,
          type: "text"
        },
        { type: "newline" },
        { type: "newline" }
      );
      const payload = {
        command: "PRINT_START",
        payload: payloadArr
      };
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
