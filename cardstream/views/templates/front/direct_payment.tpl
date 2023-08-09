<form action="{$url}" method="post" id="paymentgatewaymoduleform" style="margin-top: 1em;">
  <div class="form-group row">
    <label class='col-md-3 form-control-label required'>{l s='Card number'}</label>
    <div class="col-md-6">
      <input id="field-cardNumber" class='form-control' type="text" size="20" maxlength=20 autocomplete="off" name="cardNumber" required>
    </div>
  </div>

  <div class="form-group row">
    <label class="col-md-3 form-control-label required">{l s='Expiration (MM/YY)'}</label>
    <div class="col-md-2">
      <input id="field-cardExpiryMonth" class="form-control" name="cardExpiryMonth" size=2 maxlength=2 required>
    </div>
    <h3 class="col-md-1 col-form-label">/</h3>
    <div class="col-md-3">
      <input id="field-cardExpiryYear" class='form-control' name="cardExpiryYear" size=4 maxlength=4 required>
    </div>
  </div>

  <div class="form-group row">
    <label class="col-md-3 form-control-label required">{l s='CVC'}</label>
    <div class="col-md-3">
      <input id="field-cardCVV" class="form-control" type="text" size="4" maxlength=4 autocomplete="off" name="cardCVV" required>
    </div>
  </div>
  {foreach $device_data as $k=>$v}
    <input type="hidden" name="{$k|escape:'html':'UTF-8'}" id="{$k|escape:'html':'UTF-8'}" value="{$v|escape:'html':'UTF-8'}"/>
  {/foreach}
</form>
<script>
  var screen_width = (window && window.screen ? window.screen.width : '0');
  var screen_height = (window && window.screen ? window.screen.height : '0');
  var screen_depth = (window && window.screen ? window.screen.colorDepth : '0');
  var identity = (window && window.navigator ? window.navigator.userAgent : '');
  var language = (window && window.navigator ? (window.navigator.language ? window.navigator.language : window.navigator.browserLanguage) : '');
  var timezone = (new Date()).getTimezoneOffset();
  var java = (window && window.navigator ? navigator.javaEnabled() : false);
  document.getElementById('browserInfo[deviceIdentity]').value = identity;
  document.getElementById('browserInfo[deviceTimeZone]').value = timezone;
  document.getElementById('browserInfo[deviceCapabilities]').value = 'javascript' + (java ? ',java' : '');
  document.getElementById('browserInfo[deviceAcceptLanguage]').value = language;
  document.getElementById('browserInfo[deviceScreenResolution]').value = screen_width + 'x' + screen_height + 'x' + screen_depth;
</script>