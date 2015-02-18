<!-- layout -->
<?php 
if ($_SESSION === null) session_start();
?>


<?
# module
cInclude ("frontend", "includes/api.mathcaptcha.php");
$oMathC = new MathCaptcha();


 #{...}


	if ($_POST['formcaptcha'] == '') {
		$noerrors = false;
		$msg = mi18n("Bitte beantworten Sie die Sicherheitsabfrage!")."<br/>";
		$aErrors['formcaptcha'][] = $msg;
	}
	if ( !empty($_POST['formcaptcha']) && !$oMathC->validate($_POST['formcaptcha']) ) {
		$noerrors = false;
		$msg = mi18n("Die Antwort auf die Sicherheitsabfrage war falsch!")."<br/>";
		$aErrors['formcaptcha'][] = $msg;
	}


 #{...}


$tpl->set("s", "SICHERHEIT",         mi18n("Sicherheitsabfrage"));
$tpl->set("s", "SICHERHEITSABFRAGE", $oMathC->create() );
$tpl->set("s", "SICHERHEIT_ERROR",   mi18n("Her die Fehlermeldung...."));



# template
?>
<style>
#contactForm .contactRow INPUT#formcaptcha {
    vertical-align: middle;
    margin-top: -5px;
    width: 80px;
}

#contactForm .contactRow .formcaptcha {
    display: block;
    width:300px;
    height:15px;
    float: right;
}

#contactForm .contactRow .formcaptcha SPAN {
    padding: 0px;
    margin-top: 0px;
    margin-bottom: 0px;
    margin-left: 10px;
    margin-right: 10px;
    width: 80px;
    float: left;
}
</style>
<div class="contactRow clearfix">
    <label for="formcaptcha">{SICHERHEIT} *</label>
    
        <div class="formcaptcha">
              <span>
                  <label for="formcaptcha">{SICHERHEITSABFRAGE}</label>
              </span>
              <span>
                  <input type="text" name="formcaptcha" id="formcaptcha" class="eingabe" maxlength="50"/>
              </span>
        </div>{SICHERHEIT_ERROR}
</div>
