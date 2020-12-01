<!DOCTYPE html>
<html>
<head>
    <title>3D Secure Verification</title>
    <script language="Javascript">
        function OnLoadEvent() { document.form.submit(); }
    </script>
</head>
<body OnLoad="OnLoadEvent();">
<form name="form" action="<?php echo $data['threeDSURL']; ?>" method="post">
    <input type="hidden" name="threeDSRef" value="<?php echo $_SESSION['threeDSRef'] ; ?>" />

    <?php 
    foreach($data['threeDSRequest'] as $field => $value) {
        echo '<input type="hidden" name="$field" value="$value"/>'
    }
    ?>
    
    <noscript>
        <p>Please click</p><input id="to-asc-button" type="submit">
    </noscript>
</form>
</body>
</html>
