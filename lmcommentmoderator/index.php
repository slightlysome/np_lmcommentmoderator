<?php
/*
    LMCommentModerator Nucleus plugin
    Copyright (C) 2013-2014 Leo (http://nucleus.slightlysome.net/leo)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
	(http://www.gnu.org/licenses/gpl-2.0.html)	
*/

	$strRel = '../../../'; 
	require($strRel . 'config.php');
	include_libs('PLUGINADMIN.php');

	$oPluginAdmin = new PluginAdmin('LMCommentModerator');
	$pluginURL 	  = $oPluginAdmin->plugin->getAdminURL();
	$plugID       = $oPluginAdmin->plugin->getID();
	$plugName     = $oPluginAdmin->plugin->getName();

	_pluginDataUpgrade($oPluginAdmin);
	
	if (!($member->isLoggedIn()))
	{
		$oPluginAdmin->start();
		echo '<p>You must be logged in to use the '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' plugin admin area.</p>';
		$oPluginAdmin->end();
		exit;
	}

	$aAdminBlogs = $member->getAdminBlogs();

	if(!$aAdminBlogs)
	{
		$oPluginAdmin->start();
		echo '<p>You must be a blog admin to use the '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' plugin admin area.</p>';
		$oPluginAdmin->end();
		exit;
	}

	$action = requestVar('action');
	$filtertype = strtoupper(requestVar('filtertype'));
	$showmodcategory = strtoupper(requestVar('showmodcategory'));

	$oPluginAdmin->start("<style type='text/css'>
	<!--
		p.message {	font-weight: bold; }
		p.error { font-size: 100%; font-weight: bold; color: #880000; }
		iframe { width: 100%; height: 400px; border: 1px solid gray; }
		div.dialogbox { border: 1px solid #ddd; background-color: #F6F6F6; margin: 18px 0 0 0; }
		div.dialogbox h4 { background-color: #bbc; color: #000; margin: 0; padding: 5px; }
		div.dialogbox h4.light { background-color: #ddd; }
		div.dialogbox div { margin: 0; padding: 10px; }
		div.dialogbox button { margin: 10px 0 0 6px; float: right; }
		div.dialogbox p { margin: 0; }
		div.dialogbox p.buttons { text-align: right; overflow: auto; }
		.lmtable tr { background-color: white; }
		.lmtable tr:hover { background-color: whitesmoke; }
		.lmtable td { background-color: transparent; }
	-->
	</style>");

	if($action == 'showhelp')
	{
		echo '<p><a href="'.$pluginURL.'?skipupgradehandling=1">(Back to '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' administration)</a></p>';
		echo '<h2>Helppage for plugin: '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).'</h2>';
	
		$helpFile = $DIR_PLUGINS.$oPluginAdmin->plugin->getShortName().'/help.html';
		
       if (@file_exists($helpFile)) 
	   {
            @readfile($helpFile);
        } 
		else 
		{
            echo '<p class="error">Missing helpfile.</p>';
        }
		
		$oPluginAdmin->end();
		exit;
	}
	elseif($action == 'showspamcheckapi')
	{
		echo '<p><a href="'.$pluginURL.'?skipupgradehandling=1">(Back to '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' administration)</a></p>';
		echo '<h2>Spamcheck API for plugin: '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).'</h2>';
	
		$helpFile = $DIR_PLUGINS.$oPluginAdmin->plugin->getShortName().'/spamcheckapi.html';
		
       if (@file_exists($helpFile)) 
	   {
            @readfile($helpFile);
        } 
		else 
		{
            echo '<p class="error">Missing SpamCheck API file.</p>';
        }
		
		$oPluginAdmin->end();
		exit;
	}
	elseif($action == 'defaultformtemplates')
	{
		echo '<p><a href="'.$pluginURL.'?skipupgradehandling=1">(Back to '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' administration)</a></p>';
		echo '<h2>Default commentform templates for '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).'</h2>';
	
		echo '<h3>Comment Loggedin Form</h3>';
		$formtemplate = $oPluginAdmin->plugin->_getCommentFormTemplate('commentform-loggedin', '');
		echo '<p><pre>'.htmlspecialchars($formtemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		echo '<h3>Comment Not Loggedin Form</h3>';
		$formtemplate = $oPluginAdmin->plugin->_getCommentFormTemplate('commentform-notloggedin', '');
		echo '<p><pre>'.htmlspecialchars($formtemplate, ENT_QUOTES, _CHARSET).'</pre></p>';

		echo '<h3>Comment Loggedin Edit Form</h3>';
		$formtemplate = $oPluginAdmin->plugin->_getCommentFormTemplate('commentform-loggedin-edit', '');
		echo '<p><pre>'.htmlspecialchars($formtemplate, ENT_QUOTES, _CHARSET).'</pre></p>';

		echo '<h3>Comment Not Loggedin Edit Form</h3>';
		$formtemplate = $oPluginAdmin->plugin->_getCommentFormTemplate('commentform-notloggedin-edit', '');
		echo '<p><pre>'.htmlspecialchars($formtemplate, ENT_QUOTES, _CHARSET).'</pre></p>';

		echo '<h3>Comment Can Not Edit Form</h3>';
		$formtemplate = $oPluginAdmin->plugin->_getCommentFormTemplate('commentform-cannot-edit', '');
		echo '<p><pre>'.htmlspecialchars($formtemplate, ENT_QUOTES, _CHARSET).'</pre></p>';

		$oPluginAdmin->end();
		exit;
	}
	elseif($action == 'defaultmodcategorytemplates')
	{
		echo '<p><a href="'.$pluginURL.'?skipupgradehandling=1">(Back to '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' administration)</a></p>';
		echo '<h2>Default ModCategory templates for '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).'</h2>';
	
		echo '<h3>Show</h3>';
		$modcategorytemplate = $oPluginAdmin->plugin->_getModCategoryTemplate('O', '');
		echo '<p><pre>'.htmlspecialchars($modcategorytemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		echo '<h3>Hide</h3>';
		$modcategorytemplate = $oPluginAdmin->plugin->_getModCategoryTemplate('I', '');
		echo '<p><pre>'.htmlspecialchars($modcategorytemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		echo '<h3>Ham</h3>';
		$modcategorytemplate = $oPluginAdmin->plugin->_getModCategoryTemplate('H', '');
		echo '<p><pre>'.htmlspecialchars($modcategorytemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		echo '<h3>Spam</h3>';
		$modcategorytemplate = $oPluginAdmin->plugin->_getModCategoryTemplate('S', '');
		echo '<p><pre>'.htmlspecialchars($modcategorytemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		echo '<h3>Moderate</h3>';
		$modcategorytemplate = $oPluginAdmin->plugin->_getModCategoryTemplate('M', '');
		echo '<p><pre>'.htmlspecialchars($modcategorytemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		echo '<h3>Members</h3>';
		$modcategorytemplate = $oPluginAdmin->plugin->_getModCategoryTemplate('E', '');
		echo '<p><pre>'.htmlspecialchars($modcategorytemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		$oPluginAdmin->end();
		exit;
	}
	elseif($action == 'defaultcookiewarningtemplate')
	{
		echo '<p><a href="'.$pluginURL.'?skipupgradehandling=1">(Back to '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' administration)</a></p>';
		echo '<h2>Default Cookie Warning template for '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).'</h2>';
	
		echo '<h3>Cookie Warning</h3>';
		$cookiewarningtemplate = $oPluginAdmin->plugin->_getCookieWarningTemplate('');
		echo '<p><pre>'.htmlspecialchars($cookiewarningtemplate, ENT_QUOTES, _CHARSET).'</pre></p>';
		
		$oPluginAdmin->end();
		exit;
	}
	
	echo '<h2>'.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' Administration</h2>';

	if($action)
	{
		$actions = array('hamcomment', 'spamcomment', 'hidecomment', 'showcomment', 
					'hamfilter', 'spamfilter', 'moderatefilter', 
					'lockfilter', 'unlockfilter', 'enablefilter', 'disablefilter',
					'delcomment', 'delcomment_process',
					'delfilter', 'delfilter_process');

		if (in_array($action, $actions)) 
		{ 
			if ($manager->checkTicket())
			{
				call_user_func('_lmcommentmoderator_' . $action);
			}
			else 
			{
				echo '<p class="error">Error: Bad ticket</p>';
			}
		}
	}		

	$shownfilter = false;
	$shownmodcategory  = false;

	if(($filtertype == 'I' || $filtertype == 'M' || $filtertype == 'E' || $filtertype == 'W') && $member->isAdmin())
	{
		_showFilterType($filtertype);
		$shownfilter = true;
	}
	elseif(($showmodcategory == 'S' || $showmodcategory == 'I') && $member->isAdmin())
	{
		_showModCategory($showmodcategory);
		$shownmodcategory = true;
	}
	else
	{
		_showModCategory('M');
		$shownmodcategory = true;
	}

	$filtermembers = ($oPluginAdmin->plugin->getOption('filtermembers') == 'yes');
	$filtermail = ($oPluginAdmin->plugin->getOption('filteremail') == 'yes');
	$filterwebsite = ($oPluginAdmin->plugin->getOption('filterwebsite') == 'yes');
	$filterip = ($oPluginAdmin->plugin->getOption('filterip') == 'yes');
		
	if($member->isAdmin() && !$shownfilter && ($filtermembers || $filtermail || $filterwebsite || $filterip))
	{
		$filterlinks = '';
		
		if($filtermembers)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=m">Member</a>';
		}
		
		if($filtermail)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=e">EMail</a>';
		}

		if($filterwebsite)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=w">Website</a>';
		}

		if($filterip)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=i">IP</a>';
		}

		echo '<div class="dialogbox">';
		echo '<h4 class="light">Show filter content</h4>';
		echo '<div>';
		echo '<p>Filters: '.$filterlinks.'.</p>';
		echo '</div></div>';
	}
	
	if($member->isAdmin())
	{
		echo '<div class="dialogbox">';
		echo '<h4 class="light">Default templates</h4>';
		echo '<div><p>';
		echo 'The contents of the default commentform templates this plugin uses can be seen <a href="'.$pluginURL.'?action=defaultformtemplates">here</a>. ';
		echo 'The contents of the default ModCategory templates this plugin uses can be seen <a href="'.$pluginURL.'?action=defaultmodcategorytemplates">here</a>. ';
		echo 'The contents of the default Cookie Warning template this plugin uses can be seen <a href="'.$pluginURL.'?action=defaultcookiewarningtemplate">here</a>. ';
		echo '</p></div></div>';	
	}

	echo '<div class="dialogbox">';
	echo '<h4 class="light">Plugin help page</h4>';
	echo '<div>';
	echo '<p>The help page for this plugin is available <a href="'.$pluginURL.'?action=showhelp">here</a>. ';
	echo 'The documentation for the '.htmlspecialchars($plugName, ENT_QUOTES, _CHARSET).' SpamCheck API is available <a href="'.$pluginURL.'?action=showspamcheckapi">here</a>.</p>';
	echo '</div></div>';

	if($member->isAdmin())
	{
		echo '<div class="dialogbox">';
		echo '<h4 class="light">Plugin options page</h4>';
		echo '<div>';
		echo '<p>The options page for this plugin is available <a href="'.$CONF['AdminURL'].'index.php?action=pluginoptions&plugid='.$plugID.'">here</a>.</p>';
		echo '</div></div>';
	}
	
	$oPluginAdmin->end();
	exit;

	function _showFilterType($filtertype)
	{	
		global $oPluginAdmin, $manager, $pluginURL;
		
		$filtertypename = $oPluginAdmin->plugin->_filterGetTypeName($filtertype);
		
		echo '<div class="dialogbox">';
		echo '<h4 class="light">Filters with type: '.$filtertypename.'</h4>';
		echo '</div>';
		
		$aFilters = $oPluginAdmin->plugin->_getFilterByType($filtertype);
		
		if($aFilters)
		{
			echo '<table class="lmtable"><thead><tr>';
			echo '<th>Type</th><th>Filter Value</th><th>Category</th><th>Status</th><th>Created</th><th>Last used</th><th>Usecount</th><th colspan="6">Actions</th>';
			echo '</tr></thead>';

			foreach($aFilters AS $aFilter)
			{
				$filterid = $aFilter['filterid'];
				$status = $aFilter['status'];
				$modcategory = $aFilter['modcategory'];
				$usecount = $aFilter['usecount'];

				$timestamp = strtotime($aFilter['created']);
				$created = date('d-M-y', $timestamp).'&nbsp;'.date('H:i', $timestamp);

				$timestamp = strtotime($aFilter['lastused']);
				$lastused = date('d-M-y', $timestamp).'&nbsp;'.date('H:i', $timestamp);

				if($filtertype == 'M')
				{
					$filter = 'unknown';
					
					$memberid = intVal($aFilter['filter']);
					
					if($memberid)
					{
						$commentmember = MEMBER::createFromID($memberid);
						$filter = $commentmember->getDisplayName();
					}
				}
				else
				{
					$filter = $aFilter['filter'];
				}

				if($status == 'L') // Locked
				{
					$locklink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=unlockfilter&filterid='.$filterid).'" title="Unlock filter">Unlock</a>';
					$disablelink = ''; 
					$statustext = 'Locked';
				}
				elseif($status == 'D') // Disabled
				{
					$locklink = ''; 
					$disablelink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=enablefilter&filterid='.$filterid).'" title="Enable filter">Enable</a>';
					$statustext = 'Disabled';
				}
				else
				{
					$locklink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=lockfilter&filterid='.$filterid).'" title="Lock filter">Lock</a>';
					$disablelink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=disablefilter&filterid='.$filterid).'" title="Disable filter">Disable</a>';
					$statustext = '';
				}

				$hamlink = '';
				$spamlink = '';
				$moderatelink = '';
				
				if($modcategory != 'H')
				{
					$hamlink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=hamfilter&filterid='.$filterid).'" title="Set moderation category for the filter to Ham">Ham</a>';
				}
				
				if($modcategory != 'S')
				{
					$spamlink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=spamfilter&filterid='.$filterid).'" title="Set moderation category for the filter to Spam">Spam</a>';
				}
				
				if($modcategory != 'M')
				{
					$moderatelink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=moderatefilter&filterid='.$filterid).'" title="Set moderation category for the filter to Manual Moderation">Moderate</a>';
				}

				$dellink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?filtertype='.strtolower($filtertype).'&action=delfilter&filterid='.$filterid).'" title="Delete filter">Delete</a>';

				echo '<tr>';
				echo '<td>'.$filtertypename.'</td>';
				echo '<td>'.htmlspecialchars($filter, ENT_QUOTES, _CHARSET).'</td>';
				echo '<td>'.$oPluginAdmin->plugin->_commentModGetModCategoryName($modcategory).'</td>';
				echo '<td>'.$statustext.'</td>';
				echo '<td>'.$created.'</td>';
				echo '<td>'.$lastused.'</td>';
				echo '<td>'.$usecount.'</td>';

				echo '<td>'.$hamlink.'</td>';
				echo '<td>'.$spamlink.'</td>';
				echo '<td>'.$moderatelink.'</td>';

				echo '<td>'.$locklink.'</td>';
				echo '<td>'.$disablelink.'</td>';				
				echo '<td>'.$dellink.'</td>';

				echo '</tr>';		
			}
			echo '</table>';
		}
		else
		{
			echo '<p>There are no filters of this type.</p>';
		}

		$filtermembers = ($oPluginAdmin->plugin->getOption('filtermembers') == 'yes');
		$filtermail = ($oPluginAdmin->plugin->getOption('filteremail') == 'yes');
		$filterwebsite = ($oPluginAdmin->plugin->getOption('filterwebsite') == 'yes');
		$filterip = ($oPluginAdmin->plugin->getOption('filterip') == 'yes');

		$filterlinks = '';
		
		if($filtermembers)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=m">Member</a>';
		}
		
		if($filtermail)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=e">EMail</a>';
		}

		if($filterwebsite)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=w">Website</a>';
		}

		if($filterip)
		{
			if($filterlinks)
			{
				$filterlinks .= ', ';
			}
			
			$filterlinks .= '<a href="'.$pluginURL.'?filtertype=i">IP</a>';
		}

		echo '<p>Filters: '.$filterlinks.'. Back to: <a href="'.$pluginURL.'">Moderation queue</a></p>';
	}

	function _showModCategory($modcategory)
	{
		global $oPluginAdmin, $manager, $pluginURL, $member;
		
		echo '<div class="dialogbox"><h4 class="light">';
		if($modcategory == 'M')
		{
			echo 'Comments awaiting moderation (<a href="'.$pluginURL.'">refresh</a>)';
		}
		else
		{
			echo 'Comments with moderation category: '.$oPluginAdmin->plugin->_commentModGetModCategoryName($modcategory);
		}
		echo '</h4></div>';
		
		if($member->isAdmin())
		{
			$modcategorylinks = '';
			
			if($modcategory <> 'M')
			{
				if($modcategorylinks)
				{
					$modcategorylinks .= ', ';
				}
			
				$modcategorylinks .= '<a href="'.$pluginURL.'">Manual Moderation</a>';
			}

			if($modcategory <> 'S')
			{
				if($modcategorylinks)
				{
					$modcategorylinks .= ', ';
				}
			
				$modcategorylinks .= '<a href="'.$pluginURL.'?showmodcategory=s">Spam</a>';
			}

			if($modcategory <> 'I')
			{
				if($modcategorylinks)
				{
					$modcategorylinks .= ', ';
				}
			
				$modcategorylinks .= '<a href="'.$pluginURL.'?showmodcategory=i">Hide</a>';
			}
			
			$modcategorylinks = 'Show comments with moderation category: '.$modcategorylinks.'.';
		}

		if($modcategory == 'I' || $modcategory == 'S')
		{
			$showmodcategorypart = '&showmodcategory='.strtolower($modcategory);
			$editlocationpart = '?showmodcategory='.strtolower($modcategory);
		}
		else
		{
			$showmodcategorypart = '';
			$editlocationpart = '';
		}
		
		$aComments = $oPluginAdmin->plugin->_getCommentJoinModByABlogIdModCategory($aAdminBlogs, $modcategory);
		
		if($aComments)
		{
			echo '<table class="lmtable"><thead><tr>';
			echo '<th>Item/Comment</th><th>User/EMail/HTTP</th><th>Time/IP</th><th colspan="5">Actions</th>';
			echo '</tr></thead>';

			$aCommentId = array();
			
			foreach($aComments AS $aComment)
			{
				$timestamp = strtotime($aComment['ctime']);
				
				$datetime = date('d-M-y', $timestamp).'&nbsp;'.date('H:i', $timestamp);

				$body = $aComment['body'];
				$itemtitle = shorten($aComment['itemtitle'], 50, '...');
				$itemid = $aComment['itemid'];
				$membername = $aComment['membername'];
				
				if($membername)
				{
					$user = $membername.' (member)';
				}
				else
				{
					$user = shorten($aComment['user'], 30, '...');
				}
				$email = shorten($aComment['email'], 30, '...');
				$http = shorten($aComment['userid'], 50, '...');
				$ip = $aComment['ip'];
				$commentid = $aComment['commentid'];
				
				array_push($aCommentId, $commentid);
				
				$editlink = '<td><a href="'.$CONF['AdminURL'].'index.php?action=commentedit&commentid='.$commentid.'&location='.urlencode($pluginURL.$editlocationpart).'" title="Edit comment">Edit</a></td>';

				$showlink = '<td><a href="'.$manager->addTicketToUrl($pluginURL . '?action=showcomment&commentid='.$commentid.$showmodcategorypart).'" title="Change comment moderation category to Show">Show</a></td>';

				if($modcategory <> 'I')
				{
					$hidelink = '<td><a href="'.$manager->addTicketToUrl($pluginURL . '?action=hidecomment&commentid='.$commentid.$showmodcategorypart).'" title="Change comment moderation category to Hide">Hide</a></td>';
				}
				else
				{
					$spamlink = '';
				}

				$hamlink = '<td><a href="'.$manager->addTicketToUrl($pluginURL . '?action=hamcomment&commentid='.$commentid.$showmodcategorypart).'" title="Change comment moderation category to Ham and update spam filters">Ham</a></td>';

				if($modcategory <> 'S')
				{
					$spamlink = '<td><a href="'.$manager->addTicketToUrl($pluginURL . '?action=spamcomment&commentid='.$commentid.$showmodcategorypart).'" title="Change comment moderation category to Spam and update spam filters">Spam</a></td>';
				}
				else
				{
					$spamlink = '';
				}
								
				if($modcategory <> 'M')
				{
					$dellink = '<td><a href="'.$manager->addTicketToUrl($pluginURL . '?action=delcomment&commentid='.$commentid.$showmodcategorypart).'" title="Delete comment">Delete</a></td>';
				}
				else
				{
					$dellink = '';
				}

				echo '<tr>';
				echo '<td>';
				echo '<a href="'.createItemLink($itemid),'"><strong>'.htmlspecialchars($itemtitle, ENT_QUOTES, _CHARSET).'</strong></a><br />'; 
				echo $body.'</td>';
				echo '<td>'.htmlspecialchars($user, ENT_QUOTES, _CHARSET).'<br />';
				echo htmlspecialchars($email, ENT_QUOTES, _CHARSET).'<br />';
				echo htmlspecialchars($http, ENT_QUOTES, _CHARSET).'</td>';
				echo '<td>'.$datetime.'<br />'.$ip.'</td>';
				echo $editlink.$showlink.$hidelink.$hamlink.$spamlink.$dellink;
				echo '</tr>';		
			}
			
			echo '</table>';

			$commentidstr = implode('-', $aCommentId);
			
			$showlink = '<a href="'.$manager->addTicketToUrl($pluginURL . '?action=showcomment&commentid='.$commentidstr.$showmodcategorypart).'" title="Change comment moderation category for all comments to Show">Show</a>';

			if($modcategory <> 'I')
			{
				$hidelink = ', <a href="'.$manager->addTicketToUrl($pluginURL . '?action=hidecomment&commentid='.$commentidstr.$showmodcategorypart).'" title="Change comment moderation category for all comments to Hide">Hide</a>';
			}
			else
			{
				$hidelink = '';
			}
			
			$hamlink = ', <a href="'.$manager->addTicketToUrl($pluginURL . '?action=hamcomment&commentid='.$commentidstr.$showmodcategorypart).'" title="Change comment moderation category for all comments to Ham and update spam filters">Ham</a>';

			if($modcategory <> 'S')
			{
				$spamlink = ', <a href="'.$manager->addTicketToUrl($pluginURL . '?action=spamcomment&commentid='.$commentidstr.$showmodcategorypart).'" title="Change comment moderation category for all comments to Spam and update spam filters">Spam</a>';
			}
			else
			{
				$spamlink = '';
			}
			
			if($modcategory <> 'M')
			{
				$dellink = ', <a href="'.$manager->addTicketToUrl($pluginURL . '?action=delcomment&commentid='.$commentidstr.$showmodcategorypart).'" title="Delete all comments.">Delete</a>';
			}
			else
			{
				$dellink = '';
			}

			echo '<p>Perform actions on all comments: '.$showlink.$hidelink.$hamlink.$spamlink.$dellink.'. ';
			echo $modcategorylinks.'</p>';
		}
		else
		{
			echo '<p>';
			if($modcategory == 'M')
			{
				echo 'There are no comments awaiting moderation. ';
			}
			else
			{
				echo 'There are no comments with moderation category: '.$oPluginAdmin->plugin->_commentModGetModCategoryName($modcategory).'. ';
			}
			echo $modcategorylinks.'</p>';
		}
	}
	
	function _lmcommentmoderator_hamcomment()
	{
		_action_modcategory('H');
	}

	function _lmcommentmoderator_spamcomment()
	{
		_action_modcategory('S');
	}

	function _lmcommentmoderator_showcomment()
	{
		_action_modcategory('O');
	}

	function _lmcommentmoderator_hidecomment()
	{
		_action_modcategory('I');
	}

	function _lmcommentmoderator_hamfilter()
	{
		_action_filtermodcategory('H');
	}
	
	function _lmcommentmoderator_spamfilter()
	{
		_action_filtermodcategory('S');
	}

	function _lmcommentmoderator_moderatefilter()
	{
		_action_filtermodcategory('M');
	}

	function _lmcommentmoderator_lockfilter()
	{
		_action_filterstatus('L');
	}
	
	function _lmcommentmoderator_disablefilter()
	{
		_action_filterstatus('D');
	}
	
	function _lmcommentmoderator_unlockfilter()
	{
		_action_filterstatus('');
	}
	
	function _lmcommentmoderator_enablefilter()
	{
		_action_filterstatus('');
	}

	function _lmcommentmoderator_delcomment()
	{
		global $oPluginAdmin, $manager, $pluginURL, $showmodcategory;

		$commentidstr = requestVar('commentid');

		echo '<div class="dialogbox">';
		echo '<h4 class="light">Delete Comment(s)?</h4>';

		echo '<div><table class="lmtable"><thead><tr>';
		echo '<th>Item/Comment</th><th>User/EMail/HTTP</th><th>Time/IP</th>';
		echo '</tr></thead>';
		
		$aCommentId = explode('-', $commentidstr);
			
		foreach($aCommentId as $commentid)
		{
			$commentid = intVal($commentid);
			
			if($commentid)
			{
				$aComment = $oPluginAdmin->plugin->_getCommentJoinModByCommentID($commentid);
				
				if($aComment)
				{
					$aComment = $aComment['0'];

					$timestamp = strtotime($aComment['ctime']);
					
					$datetime = date('d-M-y', $timestamp).'&nbsp;'.date('H:i', $timestamp);

					$body = $aComment['body'];
					$itemtitle = shorten($aComment['itemtitle'], 50, '...');
					$itemid = $aComment['itemid'];
					$membername = $aComment['membername'];
					
					if($membername)
					{
						$user = $membername.' (member)';
					}
					else
					{
						$user = shorten($aComment['user'], 30, '...');
					}
					$email = shorten($aComment['email'], 30, '...');
					$http = shorten($aComment['userid'], 50, '...');
					$ip = $aComment['ip'];
					$commentid = $aComment['commentid'];
					
					echo '<tr>';
					echo '<td>';
					echo '<a href="',createItemLink($itemid),'"><strong>'.htmlspecialchars($itemtitle, ENT_QUOTES, _CHARSET).'</strong></a><br />'; 
					echo $body.'</td>';
					echo '<td>'.htmlspecialchars($user, ENT_QUOTES, _CHARSET).'<br />';
					echo htmlspecialchars($email, ENT_QUOTES, _CHARSET).'<br />';
					echo htmlspecialchars($http, ENT_QUOTES, _CHARSET).'</td>';
					echo '<td>'.$datetime.'<br />'.$ip.'</td>';
					echo '</tr>';
				}
			}
		}
		
		echo '</table></div>';

		echo '<div><form method="post" action="'.htmlspecialchars($pluginURL).'">';
		$manager->addTicketHidden();
		echo '<input type="hidden" name="action" value="delcomment_process" />';
		echo '<input type="hidden" name="commentid" value="'.$commentidstr.'" />';

		if($showmodcategory)
		{
			echo '<input type="hidden" name="showmodcategory" value="'.strtolower($showmodcategory).'" />';
		}
		
		echo '<p class="buttons">';
		echo '<input type="submit" name="button" value="Delete" />';
		echo '<input type="submit" name="button" value="Cancel" />';
		echo '</p>';
		echo '</form></div></div>';

		$oPluginAdmin->end();
		exit;
	}

	function _lmcommentmoderator_delcomment_process()
	{
		global $oPluginAdmin, $manager, $pluginURL, $member;

		if (requestVar('button') == 'Delete' && $member->isAdmin())
		{
			$commentidstr = requestVar('commentid');

			$aCommentId = explode('-', $commentidstr);
			
			foreach($aCommentId as $commentid)
			{
				$commentid = intVal($commentid);
				
				if($commentid)
				{
					$error = $oPluginAdmin->admin->deleteOneComment($commentid);
					
					if($error)
					{
						echo '<p class="error">'.$error.'</p>';
					}
					else
					{
						echo '<p class="message">Comment '.$commentid.' has been deleted.</p>';
					}
				}
			}
		}
	}
	
	function _lmcommentmoderator_delfilter()
	{
		global $oPluginAdmin, $manager, $pluginURL, $filtertype;

		$filterid = intRequestVar('filterid');

		echo '<div class="dialogbox">';
		echo '<h4 class="light">Delete Filter?</h4>';

		$aFilter = $oPluginAdmin->plugin->_getFilterByFilterId($filterid);
		
		if($aFilter)
		{
			$aFilter = $aFilter['0'];
			
			echo '<table class="lmtable"><thead><tr>';
			echo '<th>Type</th><th>Filter Value</th><th>Category</th><th>Status</th><th>Created</th><th>Last used</th><th>Usecount</th>';
			echo '</tr></thead>';

			$filterid = $aFilter['filterid'];
			$type = $aFilter['type'];
			$status = $aFilter['status'];
			$modcategory = $aFilter['modcategory'];
			$usecount = $aFilter['usecount'];

			$timestamp = strtotime($aFilter['created']);
			$created = date('d-M-y', $timestamp).'&nbsp;'.date('H:i', $timestamp);

			$timestamp = strtotime($aFilter['lastused']);
			$lastused = date('d-M-y', $timestamp).'&nbsp;'.date('H:i', $timestamp);

			$typename = $oPluginAdmin->plugin->_filterGetTypeName($type);

			if($type == 'M')
			{
				$filter = 'unknown';
				
				$memberid = intVal($aFilter['filter']);
				
				if($memberid)
				{
					$commentmember = MEMBER::createFromID($memberid);
					$filter = $commentmember->getDisplayName();
				}
			}
			else
			{
				$filter = $aFilter['filter'];
			}

			echo '<tr>';
			echo '<td>'.$typename.'</td>';
			echo '<td>'.htmlspecialchars($filter, ENT_QUOTES, _CHARSET).'</td>';
			echo '<td>'.$oPluginAdmin->plugin->_commentModGetModCategoryName($modcategory).'</td>';
			echo '<td>'.$statustext.'</td>';
			echo '<td>'.$created.'</td>';
			echo '<td>'.$lastused.'</td>';
			echo '<td>'.$usecount.'</td>';
			echo '</tr>';		

			echo '</table>';
		}

		echo '<div><form method="post" action="'.htmlspecialchars($pluginURL).'">';
		$manager->addTicketHidden();
		echo '<input type="hidden" name="action" value="delfilter_process" />';
		echo '<input type="hidden" name="filterid" value="'.$filterid.'" />';

		if($filtertype)
		{
			echo '<input type="hidden" name="filtertype" value="'.strtolower($filtertype).'" />';
		}
		
		echo '<p class="buttons">';
		echo '<input type="submit" name="button" value="Delete" />';
		echo '<input type="submit" name="button" value="Cancel" />';
		echo '</p>';
		echo '</form></div></div>';

		$oPluginAdmin->end();
		exit;
	}

	function _lmcommentmoderator_delfilter_process()
	{
		global $oPluginAdmin, $manager, $pluginURL, $member;

		if (requestVar('button') == 'Delete' && $member->isAdmin())
		{
			$filterid = intRequestVar('filterid');
			
			if($filterid)
			{
				$res = $oPluginAdmin->plugin->_deleteFilter($filterid);
				
				if($res === false)
				{
					echo '<p class="error">Error deleting filter.</p>';
				}
				else
				{
					echo '<p class="message">Filter '.$filterid.' has been deleted.</p>';
				}
			}
		}
	}

	function _action_modcategory($modcategory)
	{
		global $oPluginAdmin, $member;
		
		$categoryname = $oPluginAdmin->plugin->_commentModGetModCategoryName($modcategory);

		$commentidstr = requestVar('commentid');
		
		$aCommentId = explode('-', $commentidstr);
		
		foreach($aCommentId as $commentid)
		{
			$commentid = intVal($commentid);
			
			if($commentid)
			{
				$aComment = $oPluginAdmin->plugin->_getCommentByCommentId($commentid);

				if($aComment)
				{
					$aComment = $aComment['0'];
					
					if($member->blogAdminRights($aComment['blogid']))
					{
						if($oPluginAdmin->plugin->_updateCommentModSetModCategory($commentid, $modcategory))
						{
							echo '<p class="message">Comment '.$commentid.' has been moved to '.$categoryname.' category.</p>';

							if($modcategory == 'H' || $modcategory == 'S')
							{
								if($oPluginAdmin->plugin->_performSpamMark($commentid) === false)
								{
									echo '<p class="message">Comment '.$commentid.' SpamMark failed.</p>';
								}
							}
						}
						else
						{
							echo '<p class="error">Comment '.$commentid.' moderator category update failed.</p>';
						}
					}
				}
			}
		}
	}

	function _action_filtermodcategory($modcategory)
	{
		global $oPluginAdmin, $member;
		
		$categoryname = $oPluginAdmin->plugin->_commentModGetModCategoryName($modcategory);

		$filterid = requestVar('filterid');
		
		if($filterid)
		{
			$aFilter = $oPluginAdmin->plugin->_getFilterByFilterId($filterid);

			if($aFilter)
			{
				$aComment = $aComment['0'];
				
				if($member->isAdmin())
				{
					if($oPluginAdmin->plugin->_updateFilterSetModCategoryStatus($filterid, $modcategory, 'L'))
					{
						echo '<p class="message">Filter '.$filterid.' has been set to '.$categoryname.' category.</p>';
					}
					else
					{
						echo '<p class="error">Filter '.$filterid.' moderator category update failed.</p>';
					}
				}
			}
		}
	}

	function _action_filterstatus($status)
	{
		global $oPluginAdmin, $member;
		
		$statusname = $oPluginAdmin->plugin->_filterGetStatusName($status);

		$filterid = requestVar('filterid');
		
		if($filterid)
		{
			$aFilter = $oPluginAdmin->plugin->_getFilterByFilterId($filterid);

			if($aFilter)
			{
				$aComment = $aComment['0'];
				
				if($member->isAdmin())
				{
					if($oPluginAdmin->plugin->_updateFilterSetStatus($filterid, $status))
					{
						echo '<p class="message">Filter '.$filterid.' status has been set to '.$statusname.'.</p>';
					}
					else
					{
						echo '<p class="error">Filter '.$filterid.' status update failed.</p>';
					}
				}
			}
		}
	}

	function _pluginDataUpgrade(&$oPluginAdmin)
	{
		global $member, $manager;
		
		if (!($member->isLoggedIn()))
		{
			// Do nothing if not logged in
			return;
		}

		$extrahead = "<style type='text/css'>
	<!--
		p.message { font-weight: bold; }
		p.error { font-size: 100%; font-weight: bold; color: #880000; }
		div.dialogbox { border: 1px solid #ddd; background-color: #F6F6F6; margin: 18px 0 1.5em 0; }
		div.dialogbox h4 { background-color: #bbc; color: #000; margin: 0; padding: 5px; }
		div.dialogbox h4.light { background-color: #ddd; }
		div.dialogbox div { margin: 0; padding: 10px; }
		div.dialogbox button { margin: 10px 0 0 6px; float: right; }
		div.dialogbox p { margin: 0; }
		div.dialogbox p.buttons { text-align: right; overflow: auto; }
	-->
	</style>";

		$pluginURL = $oPluginAdmin->plugin->getAdminURL();

		$sourcedataversion = $oPluginAdmin->plugin->getDataVersion();
		$commitdataversion = $oPluginAdmin->plugin->getCommitDataVersion();
		$currentdataversion = $oPluginAdmin->plugin->getCurrentDataVersion();
		
		$action = requestVar('action');

		$actions = array('upgradeplugindata', 'upgradeplugindata_process', 'rollbackplugindata', 'rollbackplugindata_process', 'commitplugindata', 'commitplugindata_process');

		if (in_array($action, $actions)) 
		{ 
			if (!$manager->checkTicket())
			{
				$oPluginAdmin->start($extrahead);
				echo '<h2>'.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' plugin data upgrade</h2>';
				echo '<p class="error">Error: Bad ticket</p>';
				$oPluginAdmin->end();
				exit;
			} 

			if (!($member->isAdmin()))
			{
				$oPluginAdmin->start($extrahead);
				echo '<h2>'.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' plugin data upgrade</h2>';
				echo '<p class="error">Only a super admin can execute plugin data upgrade actions.</p>';
				$oPluginAdmin->end();
				exit;
			}

			$gotoadminlink = false;
			
			$oPluginAdmin->start($extrahead);
			echo '<h2>'.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' plugin data upgrade</h2>';
			
			if($action == 'upgradeplugindata')
			{
				$canrollback = $oPluginAdmin->plugin->upgradeDataTest($currentdataversion, $sourcedataversion);

				$historygo = intRequestVar('historygo');
				$historygo--;
		
				echo '<div class="dialogbox">';
				echo '<form method="post" action="'.$pluginURL.'">';
				$manager->addTicketHidden();
				echo '<input type="hidden" name="action" value="upgradeplugindata_process" />';
				echo '<input type="hidden" name="historygo" value="'.$historygo.'" />';
				echo '<h4 class="light">Upgrade plugin data</h4><div>';
				echo '<p>Taking a database backup is recommended before performing the upgrade. ';
	
				if($canrollback)
				{
					echo 'After the upgrade is done you can choose to commit the plugin data to the new version or rollback the plugin data to the previous version. ';
				}
				else
				{
					echo 'This upgrade of the plugin data is not reversible. ';
				}
				
				echo '</p><br /><p>Are you sure you want to upgrade the plugin data now?</p>';
				echo '<p class="buttons">';
				echo '<input type="hidden" name="sure" value="yes" /">';
				echo '<input type="submit" value="Perform Upgrade" />';
				echo '<input type="button" name="sure" value="Cancel" onclick="history.go('.$historygo.');" />';
				echo '</p>';
				echo '</div></form></div>';
			}
			else if($action == 'upgradeplugindata_process')
			{
				$canrollback = $oPluginAdmin->plugin->upgradeDataTest($currentdataversion, $sourcedataversion);

				if (requestVar('sure') == 'yes' && $sourcedataversion > $currentdataversion)
				{
					if($oPluginAdmin->plugin->upgradeDataPerform($currentdataversion + 1, $sourcedataversion))
					{
						$oPluginAdmin->plugin->setCurrentDataVersion($sourcedataversion);
						
						if(!$canrollback)
						{
							$oPluginAdmin->plugin->upgradeDataCommit($currentdataversion + 1, $sourcedataversion);
							$oPluginAdmin->plugin->setCommitDataVersion($sourcedataversion);					
						}
						
						echo '<p class="message">Upgrade of plugin data was successful.</p>';
						$gotoadminlink = true;
					}
					else
					{
						echo '<p class="error">Upgrade of plugin data failed.</p>';
					}
				}
				else
				{
					echo '<p class="message">Upgrade of plugin data canceled.</p>';
					$gotoadminlink = true;
				}
			}
			else if($action == 'rollbackplugindata')
			{
				$historygo = intRequestVar('historygo');
				$historygo--;
				
				echo '<div class="dialogbox">';
				echo '<form method="post" action="'.$pluginURL.'">';
				$manager->addTicketHidden();
				echo '<input type="hidden" name="action" value="rollbackplugindata_process" />';
				echo '<input type="hidden" name="historygo" value="'.$historygo.'" />';
				echo '<h4 class="light">Rollback plugin data upgrade</h4><div>';
				echo '<p>You may loose any plugin data added after the plugin data upgrade was performed. ';
				echo 'After the rollback is performed must you replace the plugin files with the plugin files for the previous version. ';
				echo '</p><br /><p>Are you sure you want to rollback the plugin data upgrade now?</p>';
				echo '<p class="buttons">';
				echo '<input type="hidden" name="sure" value="yes" /">';
				echo '<input type="submit" value="Perform Rollback" />';
				echo '<input type="button" name="sure" value="Cancel" onclick="history.go('.$historygo.');" />';
				echo '</p>';
				echo '</div></form></div>';
			}
			else if($action == 'rollbackplugindata_process')
			{
				if (requestVar('sure') == 'yes' && $currentdataversion > $commitdataversion)
				{
					if($oPluginAdmin->plugin->upgradeDataRollback($currentdataversion, $commitdataversion + 1))
					{
						$oPluginAdmin->plugin->setCurrentDataVersion($commitdataversion);
										
						echo '<p class="message">Rollback of the plugin data upgrade was successful. You must replace the plugin files with the plugin files for the previous version before you can continue.</p>';
					}
					else
					{
						echo '<p class="error">Rollback of the plugin data upgrade failed.</p>';
					}
				}
				else
				{
					echo '<p class="message">Rollback of plugin data canceled.</p>';
					$gotoadminlink = true;
				}
			}	
			else if($action == 'commitplugindata')
			{
				$historygo = intRequestVar('historygo');
				$historygo--;
				
				echo '<div class="dialogbox">';
				echo '<form method="post" action="'.$pluginURL.'">';
				$manager->addTicketHidden();
				echo '<input type="hidden" name="action" value="commitplugindata_process" />';
				echo '<input type="hidden" name="historygo" value="'.$historygo.'" />';
				echo '<h4 class="light">Commit plugin data upgrade</h4><div>';
				echo '<p>After the commit of the plugin data upgrade is performed can you not rollback the plugin data to the previous version.</p>';
				echo '</p><br /><p>Are you sure you want to commit the plugin data now?</p>';
				echo '<p class="buttons">';
				echo '<input type="hidden" name="sure" value="yes" /">';
				echo '<input type="submit" value="Perform Commit" />';
				echo '<input type="button" name="sure" value="Cancel" onclick="history.go('.$historygo.');" />';
				echo '</p>';
				echo '</div></form></div>';
			}
			else if($action == 'commitplugindata_process')
			{
				if (requestVar('sure') == 'yes' && $currentdataversion > $commitdataversion)
				{
					if($oPluginAdmin->plugin->upgradeDataCommit($commitdataversion + 1, $currentdataversion))
					{
						$oPluginAdmin->plugin->setCommitDataVersion($currentdataversion);
										
						echo '<p class="message">Commit of the plugin data upgrade was successful.</p>';
						$gotoadminlink = true;
					}
					else
					{
						echo '<p class="error">Commit of the plugin data upgrade failed.</p>';
						return;
					}
				}
				else
				{
					echo '<p class="message">Commit of plugin data canceled.</p>';
					$gotoadminlink = true;
				}
			}	
	
			if($gotoadminlink)
			{
				echo '<p><a href="'.$pluginURL.'">Continue to '.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' admin page</a>';
			}
			
			$oPluginAdmin->end();
			exit;
		}
		else
		{
			if($currentdataversion > $sourcedataversion)
			{
				$oPluginAdmin->start($extrahead);
				echo '<h2>'.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' plugin data upgrade</h2>';
				echo '<p class="error">An old version of the plugin files are installed. Downgrade of the plugin data is not supported.</p>';
				$oPluginAdmin->end();
				exit;
			}
			else if($currentdataversion < $sourcedataversion)
			{
				// Upgrade
				if (!($member->isAdmin()))
				{
					$oPluginAdmin->start($extrahead);
					echo '<h2>'.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' plugin data upgrade</h2>';
					echo '<p class="error">The plugin data needs to be upgraded before the plugin can be used. Only a super admin can do this.</p>';
					$oPluginAdmin->end();
					exit;
				}
				
				$oPluginAdmin->start($extrahead);
				echo '<h2>'.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' plugin data upgrade</h2>';
				echo '<div class="dialogbox">';
				echo '<h4 class="light">Upgrade plugin data</h4><div>';
				echo '<form method="post" action="'.$pluginURL.'">';
				$manager->addTicketHidden();
				echo '<input type="hidden" name="action" value="upgradeplugindata" />';
				echo '<p>The plugin data need to be upgraded before the plugin can be used. ';
				echo 'This function will upgrade the plugin data to the latest version.</p>';
				echo '<p class="buttons"><input type="submit" value="Upgrade" />';
				echo '</p></form></div></div>';
				$oPluginAdmin->end();
				exit;
			}
			else
			{
				$skipupgradehandling = (strstr(serverVar('REQUEST_URI'), '?') || serverVar('QUERY_STRING') || strtoupper(serverVar('REQUEST_METHOD') ) == 'POST');
							
				if($commitdataversion < $currentdataversion && $member->isAdmin() && !$skipupgradehandling)
				{
					// Commit or Rollback
					$oPluginAdmin->start($extrahead);
					echo '<h2>'.htmlspecialchars($oPluginAdmin->plugin->getName(), ENT_QUOTES, _CHARSET).' plugin data upgrade</h2>';
					echo '<div class="dialogbox">';
					echo '<h4 class="light">Commit plugin data upgrade</h4><div>';
					echo '<form method="post" action="'.$pluginURL.'">';
					$manager->addTicketHidden();
					echo '<input type="hidden" name="action" value="commitplugindata" />';
					echo '<p>If you choose to continue using this version after you have tested this version of the plugin, ';
					echo 'you have to choose to commit the plugin data upgrade. This function will commit the plugin data ';
					echo 'to the latest version. After the plugin data is committed will you not be able to rollback the ';
					echo 'plugin data to the previous version.</p>';
					echo '<p class="buttons"><input type="submit" value="Commit" />';
					echo '</p></form></div></div>';
					
					echo '<div class="dialogbox">';
					echo '<h4 class="light">Rollback plugin data upgrade</h4><div>';
					echo '<form method="post" action="'.$pluginURL.'">';
					$manager->addTicketHidden();
					echo '<input type="hidden" name="action" value="rollbackplugindata" />';
					echo '<p>If you choose to go back to the previous version of the plugin after you have tested this ';
					echo 'version of the plugin, you have to choose to rollback the plugin data upgrade. This function ';
					echo 'will rollback the plugin data to the previous version. ';
					echo 'After the plugin data is rolled back you have to update the plugin files to the previous version of the plugin.</p>';
					echo '<p class="buttons"><input type="submit" value="Rollback" />';
					echo '</p></form></div></div>';

					echo '<div class="dialogbox">';
					echo '<h4 class="light">Skip plugin data commit/rollback</h4><div>';
					echo '<form method="post" action="'.$pluginURL.'">';
					$manager->addTicketHidden();
					echo '<input type="hidden" name="skipupgradehandling" value="1" />';
					echo '<p>You can choose to skip the commit/rollback for now and test the new version ';
					echo 'of the plugin with upgraded data.'; 
					echo 'You will be asked to commit or rollback the plugin data upgrade the next time ';
					echo 'you use the link to the plugin admin page.</p>';
					echo '<p class="buttons"><input type="submit" value="Skip" />';
					echo '</p></form></div></div>';

					$oPluginAdmin->end();
					exit;
				}
			}
		}
	}
?>