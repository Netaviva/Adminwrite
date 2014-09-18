<?php

session_start();

require_once('constant.php');
require_once('rewrite.class.php');
require_once('admin.class.php');

$oRewrite = new Linko_Rewrite;
Linko_Admin::set();

// Admin Login
$sUsername = 'admin';
$sPassword = 'admin';

$aErrors = array();
$sBase = $oRewrite->getBase();
$aRules = $oRewrite->getRules();
$aLines = $oRewrite->read($oRewrite->getRulesFile());
$bLogged = isset($_COOKIE['linkodev_admin_hash']) ? true : false;

$bEdit = false;
$iEdit = 0;

$_GET = Linko_Text::clean($_GET);

$sAction = isset($_GET['action']) ? $_GET['action'] : null;

if($bLogged)
{
	if($sAction == 'change_base')
	{
		$sNewBase = $_POST['base'];
		if(Linko_Admin::changeBase($sNewBase))
		{
			Linko_Request::redirect('index.php', 'RewriteBase Changed Successfully');	
		}
	}
	if($sAction == 'delete')
	{
		if(!$iLine = $_GET['line'])
		{
			Linko_Request::redirect('index.php');	
		}
		
		if(Linko_Admin::removeRule($iLine))
		{
			Linko_Request::redirect('index.php', 'Rule Removed Successfully');
		}
	}
	else if($sAction == 'edit')
	{
		$bEdit = true;
		
		if(!$iEdit = $_GET['line'])
		{
			Linko_Request::redirect('index.php');	
		}
		
		if(isset($_POST['edit_rule']))
		{
			if(Linko_Admin::editRule($iEdit, $_POST['expression'], $_POST['redirect'], $_POST['flag']))
			{
				Linko_Request::redirect('index.php', 'Rule Updated Successfully');	
			}
		}
	}
	else if($sAction == 'add_rule')
	{
		if(isset($_POST['add_rule']))
		{
			
			$sExpr = $_POST['expression'];
			$sRedirect = $_POST['redirect'];
			$sFlag = $_POST['flag'];
			
			if(empty($sExpr) || empty($sRedirect) || empty($sFlag))
			{
				$aErrors[] = 'One or More Required Field(s) Empty';
			}
			
			if($sCompilationError = Linko_Util::pregValidate('/' . $oRewrite->prepareExpression($sExpr) . '/'))
			{
				$aErrors[] = $sCompilationError;	
			}
			
			if(!count($aErrors))
			{
				Linko_Admin::addRule($sExpr, $sRedirect, $sFlag);
				
				Linko_Request::redirect('index.php', 'Rule Added Successfully');
			}
		}	
	}
	else if($sAction == 'logout')
	{
		setcookie('linkodev_admin_hash', '', -1);
		
		Linko_Request::redirect('index.php');
			
	}
}
else
{
	if($sAction == 'login')
	{
		if(($_POST['user'] != $sUsername) || ($_POST['pass'] != $sPassword))
		{
			Linko_Request::redirect('index.php', 'Invalid Login Information');
		}
		
		$sHash = Linko_Admin::createHash($_POST['user'], $_POST['pass']);
		
		setcookie('linkodev_admin_hash', $sHash, time() * 0);
		
		Linko_Request::redirect('index.php');
	}
}

$sFlash = Linko_Flash::get();

Linko_Flash::remove();

?>

<html>
<head>
<title><?php echo Linko_Admin::title(); ?></title>
<link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div id="header-wrapper">
	<div id="header">
    	<h1><?php echo Linko_Admin::title(); ?></h1>
    </div>
</div>
<div id="content-wrapper">
	<div id="content">
    	<?php if($sFlash): ?>
        	<div id="global-message">
            	<?php echo $sFlash; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!$bLogged): ?>
        	<div id="loginform">
            	<form action="index.php?action=login" method="post">
                	<input class="loginuser" type="text" name="user" value="username" /> 
                    <input class="loginpass" type="password" name="pass" value="password" />
                    <input class="loginbutton" type="submit" value="Login">
                </form>
            </div>
        <?php else: ?>
			<?php if(count($aErrors)): ?>
                <div id="errors">
                    <?php foreach($aErrors as $sError): ?>
                        <div class="error"><?php echo $sError; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div id="errors">
                
            </div>
            <h2>Rules</h2>
            
            <div class="base clearfix">
                <h4>Rewrite Base: </h4>
                <form action="index.php?action=change_base" method="post">
                <input type="text" name="base" size="80" value="<?php echo $sBase; ?>" />
                <input type="submit" class="button" value="Change Base" />
                </form>
            </div>
            <div class="table">
                <div class="thead clearfix">
                    <div class="th first">
                        Expression
                    </div>
                    <div class="th">
                        Location
                    </div>
                    <div class="th">
                        Flags
                    </div>
                    <div class="th delete last">
                        Actions
                    </div>
                </div>
                <div class="tbody">
                    <?php foreach($aRules as $iLine => $aRule): ?>
						<?php if($bEdit && ($iEdit == $iLine)): ?>
                        <div class="editform">
                        <form action="index.php?action=edit&line=<?php echo $iEdit; ?>" method="post">
                        <div class="tr clearfix">
                            <div class="td">
                                <input type="text" name="expression" value="<?php echo $aRule['expression']; ?>" />
                            </div>
                            <div class="td">
                                <input type="text" name="redirect" value="<?php echo $aRule['location']; ?>" />
                            </div> 
                            <div class="td">
                                <input type="text" name="flag" value="<?php echo $aRule['flag_raw']; ?>">
                            </div>
                            <div class="td delete">
                            	<input type="submit" class="button" name="edit_rule" value="Save">
                            </div>                   
                        </div>
                        </form>
                        </div>
                        <?php else: ?>
                        <div class="tr clearfix">
                            <div class="td">
                                <?php echo $aRule['expression']; ?>
                            </div>
                            <div class="td">
                                <?php echo $aRule['location']; ?>
                            </div> 
                            <div class="td">
                                <?php echo $aRule['flag_raw']; ?>
                            </div>
                            <div class="td delete">
                            	<a href="?action=edit&line=<?php echo $iLine; ?>">
                                    Edit
                                </a>
                                <a onClick="if(confirm('Are you sure you want to remove this rule?')){return true;} return false;" href="?action=delete&line=<?php echo $iLine; ?>">
                                    Delete
                                </a>
                            </div>                   
                        </div>                        
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div><!-- end #content -->
</div>

<?php if($bLogged): ?>
<div id="add-rule">
	<form action="index.php?action=add_rule" method="post">
	<div class="form">
        <div class="form-expression">
            <label>Expression:</label> 
            <input type="text" name="expression" value="login" />
        </div>
        <div class="form-redirect">
            <label>Redirect:</label>
            <input type="text" name="redirect" value="login.php" />
        </div>
        <div class="form-flag">
            <label>Flag:</label>
            <input type="text" name="flag" value="L,NC" />
        </div>

        <div class="form-submit">
        	<input class="button" type="submit" name="add_rule" value="Add Rule" />
        </div>
    </div>
    </form>
</div>
<?php endif; ?>
</body>
</html>