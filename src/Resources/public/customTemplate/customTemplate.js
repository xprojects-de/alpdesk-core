

console.log("Hello from Custom-Script");

// Use pure Javascript
var e = document.getElementById('alpdeskCustomTemplate');
console.log(e);
if (e !== null && e !== 'undefined') {
  var t = setTimeout(function changeColor() {
    e.style.backgroundColor = "#99ff99";
  }, 2000);
}

// also use jquery if in ngScriptUrl-Array
if ($('#alpdeskCustomTemplate').length > 0) {
  console.log($('#alpdeskCustomTemplate'));
  $('#alpdeskCustomTemplate').append('<hr><div class="alpdeskCustomTemplate"><p>Append by jQuery</p><button id="dashboardButton">Click to Dashboard</button><button id="selfButton">Click to call me with Params</button></div>');
  $("#dashboardButton").click(function () {
    // You can also interact with other components by CustomEvent "alpdesk"
    var event = new CustomEvent("alpdesk", {
      detail: {
        type: 'route',
        target: 'dashboard'
      }
    });
    document.dispatchEvent(event);
  });
  $("#selfButton").click(function () {
    // You can also interact with other components by CustomEvent "alpdesk"
    var event = new CustomEvent("alpdesk", {
      detail: {
        type: 'route',
        target: 'customTemplate',
        params: {
          key1: 'Value1',
          key2: 'Value2',
          key3: 'Value3',
        }
      }
    });
    document.dispatchEvent(event);
  });

}
