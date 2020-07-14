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
	
	See lmcommentmoderator/help.html for plugin description, install, usage and change history.
*/
class NP_LMCommentModerator extends NucleusPlugin
{
	var $aUser;
	
	// name of plugin 
	function getName()
	{
		return 'LMCommentModerator';
	}

	// author of plugin
	function getAuthor()
	{
		return 'Leo (http://nucleus.slightlysome.net/leo)';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL()
	{
		return 'http://nucleus.slightlysome.net/plugins/lmcommentmoderator';
	}

	// version of the plugin
	function getVersion()
	{
		return '1.0.0';
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{
		return 'Comment moderation plugin. The plugin will let you keep commenting open for all, '
				.'while you will still have full control over which comments that get posted to the blog. '
				.'Unregistered commenters will have the possibility to edit their comments for one hour.';
	}

	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			case 'SqlApi':
				return 1;
			case 'HelpPage':
				return 1;
			default:
				return 0;
		}
	}
	
	function hasAdminArea()
	{
		return 1;
	}
	
	function getMinNucleusVersion()
	{
		return '360';
	}
	
	function getTableList()
	{	
		return 	array($this->getTableCommentMod(), $this->getTableUser(), $this->getTableFilter());
	}
	
	function getTableCommentMod()
	{
		// select * from nucleus_plug_lmcommentmoderator_commentmod;
		return sql_table('plug_lmcommentmoderator_commentmod');
	}

	function getTableUser()
	{
		// select * from nucleus_plug_lmcommentmoderator_user;
		return sql_table('plug_lmcommentmoderator_user');
	}
	
	function getTableFilter()
	{
		// select * from nucleus_plug_lmcommentmoderator_filter;
		return sql_table('plug_lmcommentmoderator_filter');
	}

	function getEventList() 
	{ 
		return array('AdminPrePageFoot', 'QuickMenu', 'PostAddComment', 'PostDeleteComment', 
			'LMReplacementVars_CommentsExtraQuery', 'LMReplacementVars_EditCommentFormExtras', 
			'PreUpdateComment', 'LMReplacementVars_PreForm', 'PostAuthentication', 
			'LMReplacementVars_CommentFormInComment', 'FormExtra', 'LMCommentModerator_SpamCheck',
			'LMCommentModerator_SpamMark', 'TemplateExtraFields'); 
	}
	
	function getPluginDep() 
	{
		return array('NP_LMReplacementVars');
	}

	function install()
	{
		$sourcedataversion = $this->getDataVersion();

		$this->upgradeDataPerform(1, $sourcedataversion);
		$this->setCurrentDataVersion($sourcedataversion);
		$this->upgradeDataCommit(1, $sourcedataversion);
		$this->setCommitDataVersion($sourcedataversion);					
	}
	
	function unInstall()
	{
		global $manager;
		
		if ($this->getOption('del_uninstall') == 'yes')	
		{
			foreach ($this->getTableList() as $table) 
			{
				sql_query("DROP TABLE IF EXISTS ".$table);
			}
		}
	}

	function event_AdminPrePageFoot(&$data)
	{
		// Workaround for missing event: AdminPluginNotification
		$data['notifications'] = array();
			
		$this->event_AdminPluginNotification($data);
			
		foreach($data['notifications'] as $aNotification)
		{
			echo '<h2>Notification from plugin: '.htmlspecialchars($aNotification['plugin'], ENT_QUOTES, _CHARSET).'</h2>';
			echo $aNotification['text'];
		}
	}
	
	////////////////////////////////////////////////////////////
	//  Events
	function event_AdminPluginNotification(&$data)
	{
		global $member, $manager;
		
		$actions = array('overview', 'pluginlist', 'plugin_LMCommentModerator');
		$text = "";
		
		if(in_array($data['action'], $actions))
		{			
			if(!$this->_checkReplacementVarsSourceVersion())
			{
				$text .= '<p><b>The installed version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin needs version '.$this->_needReplacementVarsSourceVersion().' or later of the LMReplacementvars plugin to function properly.</b> The latest version of the LMReplacementvars plugin can be downloaded from the LMReplacementvars <a href="http://nucleus.slightlysome.net/plugins/lmreplacementvars">plugin page</a>.</p>';
			}

			$sourcedataversion = $this->getDataVersion();
			$commitdataversion = $this->getCommitDataVersion();
			$currentdataversion = $this->getCurrentDataVersion();
		
			if($currentdataversion > $sourcedataversion)
			{
				$text .= '<p>An old version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin files are installed. Downgrade of the plugin data is not supported. The correct version of the plugin files must be installed for the plugin to work properly.</p>';
			}
			
			if($currentdataversion < $sourcedataversion)
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is for an older version of the plugin than the version installed. ';
				$text .= 'The plugin data needs to be upgraded or the source files needs to be replaced with the source files for the old version before the plugin can be used. ';

				if($member->isAdmin())
				{
					$text .= 'Plugin data upgrade can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.';
				}
				
				$text .= '</p>';
			}
			
			if($commitdataversion < $currentdataversion && $member->isAdmin())
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is upgraded, but the upgrade needs to commited or rolled back to finish the upgrade process. ';
				$text .= 'Plugin data upgrade commit and rollback can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.</p>';
			}
		}
		
		if($text)
		{
			array_push(
				$data['notifications'],
				array(
					'plugin' => $this->getName(),
					'text' => $text
				)
			);
		}
	}

	function event_QuickMenu(&$data) 
	{
		global $member;

		if (!$member->isAdmin() && !count($member->getAdminBlogs())) { return; }
		
		array_push($data['options'],
				array('title' => 'LMCommentModerator',
					'url' => $this->getAdminURL(),
					'tooltip' => 'Administer NP_LMCommentModerator'));
	}

	function event_PostAddComment(&$data)
	{
		global $member, $CONF;
		
		$commentid = $data['commentid'];
		$userid = null;
		$userkey = false;
	
		if(!$member->isLoggedIn())
		{
			$email = $data['comment']['email'];
			$username = $data['comment']['user'];
			$website = $data['comment']['userid'];

			if($this->aUser)
			{
				$userid = $this->aUser['userid'];
				$userkey = $this->aUser['userkey'];
				
				$ret = $this->_updateUserSetLastCommentId($userid, $commentid);
				if(ret === false) { return false; }
				
				$ret = $this->_updateUserSetLastCommentMeta($userid, $username, $email, $website);
				if(ret === false) { return false; }
			}
			else
			{
				$userkey = $this->_genUserKey();
				if($userkey === false) { return false; }
				
				$userid = $this->_insertUser($username, $email, $website, $userkey, $commentid);
				if($userid === false) { return false; }

				$aUser = $this->_getUserByUserId($userid);
				if(!$aUser) { return false; }
				$this->aUser = $aUser['0'];
			}
		}

		$modcategory = '';
		
		if($member->isLoggedIn())
		{
			if($member->blogAdminRights($blogid) || $this->getOption('spamcheckmembers') == 'no')
			{
				$modcategory = 'O';
			}
		}

		$rawbody = postVar('body');

		$ret = $this->_insertCommentMod($commentid, $userid, $modcategory, $rawbody, '', '', '');
		if($ret === false) { return false; }

		if(!$modcategory)
		{
			$modcategory = $this->_performSpamCheck($commentid, '');
			if($modcategory === false) { return false; }
			
			if($modcategory == 'S')
			{
				// Use the spammers time to purge old spam.
				$ret = $this->_purgeSpam();
				if($ret === false) { return false; }
			}
		}
		
		if($userkey)
		{
			$lifetime = time() + 31104000;
			setcookie($CONF['CookiePrefix'] . 'lmcommentmoderator_userkey', $userkey, $lifetime, '/', '', 0);
		}
	}

	function event_PostDeleteComment(&$data)
	{
		$commentid = $data['commentid'];
	
		$this->_deleteCommentMod($commentid);
	}

	function event_LMReplacementVars_CommentsExtraQuery(&$data)
	{
		global $member;
		
		$blogid = $data['blog']->getID();
		
		if($this->aUser)
		{
			$userid = $this->aUser['userid'];
		}
		else
		{
			$userid = false;
		}
		
		$data['extraquery']['from']['lmcommentmoderator'] = $this->getTableCommentMod().' as lmcm';
		$data['extraquery']['where']['lmcommentmoderator'] = 'lmcm.commentid = c.cnumber AND (lmcm.modcategory IN ("H", "O") ';
		
		if($member->isLoggedIn())
		{
			$data['extraquery']['where']['lmcommentmoderator'] .= ' OR (lmcm.modcategory IN ("M", "S", "I") AND c.cmember = '.$member->getID().') ';
			$data['extraquery']['where']['lmcommentmoderator'] .= ' OR (lmcm.modcategory = "E") ';
			
			if($member->blogAdminRights($blogid))
			{
				$data['extraquery']['where']['lmcommentmoderator'] .= ' OR (lmcm.modcategory IN ("M")) ';
				
				$commentshow = intRequestVar('commentshow');
				
				if($commentshow)
				{
					$data['extraquery']['where']['lmcommentmoderator'] .= ' OR (c.cnumber = '.$commentshow.') ';
				}

				$commentedit = intRequestVar('commentedit');
				
				if($commentedit)
				{
					$data['extraquery']['where']['lmcommentmoderator'] .= ' OR (c.cnumber = '.$commentedit.') ';
				}
			}
		}
		elseif($userid)
		{
			$data['extraquery']['where']['lmcommentmoderator'] .= ' OR (lmcm.modcategory IN ("M", "S", "I") AND lmcm.userid = '.$userid.') ';
		}

		$data['extraquery']['where']['lmcommentmoderator'] .= ') ';		
	}

	function event_LMReplacementVars_EditCommentFormExtras(&$data)
	{
		global $member;
		
		$comment = $data['comment'];

		if($member->blogAdminRights($comment['blogid']))
		{
			$aCommentMod = $this->_getCommentModByCommentId($comment['commentid']);

			if(!$aCommentMod) { return false; }
			
			$aCommentMod = $aCommentMod['0'];
			$modcategory = $aCommentMod['modcategory'];
			
			echo '<h3>LMCommentModerator</h3>';
			$this->_formModCategory($modcategory, '');
		
			$this->_showModCommentMetaData($aCommentMod);
		}
	}

	function event_PreUpdateComment(&$data)
	{
		global $member;
		
        $commentid = intRequestVar('commentid');
		$modcategory = '';
		$memberid = null;
		
		$aComment = $this->_getCommentJoinModByCommentID($commentid);
		if(!$aComment) { return false; }
		
		$aComment = $aComment['0'];
		$oldmodcategory = $aCommentMod['modcategory'];

		if($member->isLoggedIn())
		{			
			if($member->blogAdminRights($aComment['blogid']))
			{
				$modcategory = requestVar('plug_lmcommentmoderator_modcategory');
				
				if(!$modcategory)
				{
					$modcategory = 'O';
				}
			} 
			elseif($this->getOption('spamcheckmembers') == 'no')
			{
				$modcategory = $oldmodcategory;
			}
			
			$memberid = $member->getID();
		}

		$rawbody = postVar('body');

		$ret = $this->_updateCommentModSetModCategoryRawBody($commentid, $modcategory, $rawbody);
		if($ret === false) { return false; }

		$ret = $this->_updateCommentModSetEdit($commentid, $memberid);
		if($ret === false) { return false; }

		if($modcategory)
		{
			if(($modcategory == 'S' || $modcategory == 'H') && $modcategory <> $oldmodcategory)
			{
				$ret = $this->_performSpamMark($commentid);
				if($ret === false) { return false; }
			}
		}
		else
		{
			$ret = $this->_performSpamCheck($commentid, $oldmodcategory);
			if($ret === false) { return false; }
		}
	}

	function event_LMReplacementVars_PreForm(&$data)
	{
		global $member, $itemid, $DIR_NUCLEUS, $manager;
		
		$type = $data['type'];
		
		$commentid    = $data['commentid'];
		$retry        = $data['retry'];
		$templatename = $data['templatename'];
		
		$commentreply = intRequestVar('commentreply');
		$commentedit  = intRequestVar('commentedit');

		$destinationurl = $this->_createItemUrl($itemid, '', null);
		$destinationurl .= '#comment<%commentid%>';
		$data['formdata']['destinationurl'] = $destinationurl;
		
		$commenturlextra = '';

		if($type == 'commentform-notloggedin' && !$commentedit)
		{
			if(!$member->isLoggedIn() && $this->aUser && !$retry)
			{
				$data['formdata']['user']   = $this->aUser['username'];
				$data['formdata']['email']  = $this->aUser['email'];
				$data['formdata']['userid'] = $this->aUser['website'];
			}
		}

		if(substr($type, 0, 12) == 'commentform-')
		{
			if($commentid && $commentreply == $commentid)
			{
				$formtype = $type.'-reply';

				$data['formdata']['commentid']  = $commentid;
			}
			elseif($commentid && $commentedit == $commentid)
			{
				$canedit = $this->_canEdit($commentid);
				
				if($canedit)
				{
					if($member->isLoggedIn())
					{
						if($member->blogAdminRights($blogid))
						{
							$aCommentMod = $this->_getCommentMod($commentid);
							if($aCommentMod === false) { return false; }

							$aCommentMod = $aCommentMod['0'];
							$modcategory = $aCommentMod['modcategory'];
							
							if($modcategory == 'S' || $modcategory == 'I')
							{
								$commenturlextra = 'commentshow='.$commentid;
							}
						}
					}
					
					$comment = COMMENT::getComment($commentid);

					if($comment['memberid'])
					{
						$formtype = 'commentform-loggedin-edit';
					}
					else
					{
						$formtype = 'commentform-notloggedin-edit';
					}
					
					$data['formdata']['commentid']  = $commentid;

					if(!$retry)
					{
						$eventdata = array('comment' => &$comment);
						$manager->notify('PrepareCommentForEdit', $eventdata);

						$comment['body'] = str_replace('<br />', '', $comment['body']);
						$comment['body'] = preg_replace("#<a href=['\"]([^'\"]+)['\"]( rel=\"nofollow\")?>[^<]*</a>#i", "\\1", $comment['body']);

						if(!$comment['memberid'])
						{
							$data['formdata']['email']  = htmlspecialchars($comment['email'], ENT_QUOTES, _CHARSET);
							$data['formdata']['user']   = htmlspecialchars($comment['user'], ENT_QUOTES, _CHARSET);
							$data['formdata']['userid'] = htmlspecialchars($comment['userid'], ENT_QUOTES, _CHARSET);
						}

						$data['formdata']['body'] = $comment['body'];
					}
				}
				else
				{
					$formtype = 'commentform-cannot-edit';
				}
			}
			else
			{
				$formtype = $type;
			}

			if($formtype)
			{
				$data['contents'] = $this->_getCommentFormTemplate($formtype, $templatename);
			}
		}
		
		$commenturl = $this->_createItemUrl($itemid, $commenturlextra, $commentid);
		$data['formdata']['commenturl']  = $commenturl;
	}
	
	function event_PostAuthentication(&$data)
	{
		global $member, $CONF;
		
		$this->aUser = array();
		$aUser = false;
		
		if(!$member->isLoggedIn())
		{
			$userkey = cookieVar($CONF['CookiePrefix'].'lmcommentmoderator_userkey');

			if($userkey)
			{
				$aUser = $this->_getUserByUserKey($userkey);
				if($aUser === false) { return false; }
			}
		}
		
		if($aUser)
		{
			$this->aUser = $aUser['0'];
			$this->_updateUserSetLatestUse($this->aUser['userid']);
		}
	}

	function event_LMReplacementVars_CommentFormInComment(&$data)
	{
		global $member;
		
		$commentid = $data['comment']['commentid'];
		
		$commentreply = intRequestVar('commentreply');
		$commentedit  = intRequestVar('commentedit');
		
		if(($commentid == $commentedit && $this->_canEdit($commentid)) || $commentid == $commentreply)
		{
			$data['continue'] = true;
		}
	}
	
	function event_FormExtra(&$data)
	{
		global $member, $blogid;

		$commentedit  = intRequestVar('commentedit');
		$type = $data['type'];

		if(substr($type, 0, 12) == 'commentform-' && $commentedit && $member->isLoggedIn())
		{
			if($member->blogAdminRights($blogid))
			{
				$aCommentMod = $this->_getCommentModByCommentId($commentedit);

				if(!$aCommentMod) { return false; }
				
				$aCommentMod = $aCommentMod['0'];
				$modcategory = $aCommentMod['modcategory'];
				
				$this->_formModCategory($modcategory, 'formfield');

				$this->_showModCommentMetaData($aCommentMod);
			}
		}
	}

	function event_LMCommentModerator_SpamCheck(&$data)
	{
		$spamcheck = $data['spamcheck'];

		if(!$spamcheck['result'])
		{
			$result = false;
			$message = false;

			if ($this->getOption('spamchecktestmode') == 'yes')
			{
				$body = $spamcheck['body'];
				
				if(strpos($body, 'Spam') !== false)
				{
					$result = 'S';
					$message = "Test Spam";
				}
				elseif(strpos($body, 'Ham') !== false)
				{
					$result = 'H';
					$message = "Test Ham";
				}
			}
			else
			{
				$memberid = $spamcheck['memberid'];
				$ip = $spamcheck['ip'];
				$email = $spamcheck['email'];
				$website = $spamcheck['url'];
				
				if(!$result && $memberid && $this->getOption('filtermembers') == 'yes')
				{
					$result = $this->_checkFilter('M', $memberid);
					
					if($result)
					{
						$message = "Member filter";
					}
				}
				
				if(!$result && $ip && $this->getOption('filterip') == 'yes')
				{
					$result = $this->_checkFilter('I', $ip);
					
					if($result)
					{
						$message = "IP filter";
					}
				}

				if(!$result && $email && $this->getOption('filteremail') == 'yes')
				{
					$result = $this->_checkFilter('E', $email);
					
					if($result)
					{
						$message = "EMail filter";
					}
				}

				if(!$result && $website && $this->getOption('filterwebsite') == 'yes')
				{
					$result = $this->_checkFilter('W', $website);
					
					if($result)
					{
						$message = "Website filter";
					}
				}
			}

			if($result)
			{
				$spamcheck['result'] = $result;
				$spamcheck['message'] = $message;
				$spamcheck['plugin'] = $this->getName();
			}
		}
	}
	
	function event_LMCommentModerator_SpamMark(&$data)
	{
		$spammark = $data['spammark'];
		$modcategory = $spammark['result'];
		$memberid = $spammark['memberid'];
		
		if($memberid && $this->getOption('filtermembers') == 'yes')
		{
			$ret = $this->_addToFilter('M', $memberid, $modcategory);
			if($ret === false) { return false; }
		}
		
		$email = $spammark['email'];
		if($email && $this->getOption('filteremail') == 'yes')
		{
			$ret = $this->_addToFilter('E', $email, $modcategory);
			if($ret === false) { return false; }
		}
		
		$website = $spammark['url'];
		if($website && $this->getOption('filterwebsite') == 'yes')
		{
			$ret = $this->_addToFilter('W', $website, $modcategory);
			if($ret === false) { return false; }
		}
		
		$ip = $spammark['ip'];
		if($ip && $this->getOption('filterip') == 'yes')
		{
			$ret = $this->_addToFilter('I', $ip, $modcategory);
			if($ret === false) { return false; }
		}
	}

	function event_TemplateExtraFields(&$data) 
	{
		$data['fields']['NP_LMCommentModerator'] = array(
			'lmcommentmoderator_commentform-loggedin' => 'Comment Loggedin Form',
			'lmcommentmoderator_commentform-notloggedin' => 'Comment Not Loggedin Form',
			'lmcommentmoderator_commentform-loggedin-edit' => 'Comment Loggedin Edit Form',
			'lmcommentmoderator_commentform-notloggedin-edit' => 'Comment Not Loggedin Edit Form',
			'lmcommentmoderator_commentform-cannot-edit' => 'Comment Can Not Edit Form',
			'lmcommentmoderator_modcategory_show' => 'ModCategory Show',
			'lmcommentmoderator_modcategory_hide' => 'ModCategory Hide',
			'lmcommentmoderator_modcategory_ham' => 'ModCategory Ham',
			'lmcommentmoderator_modcategory_spam' => 'ModCategory Spam',
			'lmcommentmoderator_modcategory_moderate' => 'ModCategory Moderate',
			'lmcommentmoderator_modcategory_members' => 'ModCategory Members',
			'lmcommentmoderator_cookiewarning' => 'Cookie Warning'
		);
	}

	////////////////////////////////////////////////////////////
	//  Handle skin vars
	function doTemplateCommentsVar(&$item, &$comment, $vartype, $templatename = '')
	{
		global $manager;

		$aArgs = func_get_args(); 
		$num = func_num_args();

		$aTemplateCommentsVarParm = array();
		
		for($n = 4; $n < $num; $n++)
		{
			$parm = explode("=", func_get_arg($n));
			
			if(is_array($parm) && count($parm) == 2)
			{
				$aTemplateCommentsVarParm[$parm['0']] = $parm['1'];
			}
		}

		switch (strtoupper($vartype))
		{
			case 'MODCATEGORY':
				$this->doTemplateCommentsVar_modcategory($item, $comment, $templatename, $aTemplateCommentsVarParm);
				break;
			case 'COMMENTACTIONS':
				$this->doTemplateCommentsVar_commentactions($item, $comment, $aTemplateCommentsVarParm);
				break;
			case 'COOKIEWARNING':
				$this->doTemplateCommentsVar_cookiewarning($item, $comment, $templatename, $aTemplateCommentsVarParm);
				break;
			default:
				echo "Unknown vartype: ".$vartype;
		}
	}
	
	function doTemplateCommentsVar_modcategory(&$item, &$comment, $templatename, $aTemplateCommentsVarParm)
	{
		$commentid = $comment['commentid'];
		
		$aModComment = $this->_getCommentModByCommentId($commentid);
		if($aModComment === false) { return false; }
		
		if($aModComment)
		{
			$aModComment = $aModComment['0'];
			
			$modcategory = $aModComment['modcategory'];
			
			echo $this->_getModCategoryTemplate($modcategory, $templatename);
		}
	}

	function doTemplateCommentsVar_commentactions(&$item, &$comment, $aTemplateCommentsVarParm)
	{
		global $member, $CONF, $manager;
		
		$commentedit  = intRequestVar('commentedit');

		$commentid = $comment['commentid'];
		$blogid = $comment['blogid'];
		$itemid = $comment['itemid'];
		
		$canmoderate = false;
		$canedit = false;
		
		if($commentid != $commentedit)
		{
			$aCommentMod = $this->_getCommentModByCommentId($commentid);
			if($aCommentMod == false) { return false; }
			$aCommentMod = $aCommentMod['0'];

			$modcategory = $aCommentMod['modcategory'];
			
			$canedit = $this->_canEdit($commentid);

			if($member->isLoggedIn())
			{
				if($modcategory == 'M' && $member->blogAdminRights($blogid))
				{
					$canmoderate = true;
				}
			}
		}

		if($canmoderate || $canedit)
		{
			$commenturl = $this->_createItemUrl($itemid, 'commentedit='.$commentid, $commentid);
			
			echo '<a href="'.$commenturl.'" title="Edit comment">Edit</a>'; 

			if($canmoderate)
			{
				if($modcategory <> 'O')
				{
					$url = $manager->addTicketToUrl($CONF['ActionURL'].'?action=plugin&name=lmcommentmoderator&type=showcomment&commentid='.$commentid);
					echo ' <a href="'.htmlspecialchars($url, ENT_QUOTES, _CHARSET).'" title="Move comment to Show category">Show</a>'; 
				}

				if($modcategory <> 'I')
				{
					$url = $manager->addTicketToUrl($CONF['ActionURL'].'?action=plugin&name=lmcommentmoderator&type=hidecomment&commentid='.$commentid);
					echo ' <a href="'.htmlspecialchars($url, ENT_QUOTES, _CHARSET).'" title="Move comment to Hide category">Hide</a>'; 
				}

				if($modcategory <> 'H')
				{
					$url = $manager->addTicketToUrl($CONF['ActionURL'].'?action=plugin&name=lmcommentmoderator&type=hamcomment&commentid='.$commentid);
					echo ' <a href="'.htmlspecialchars($url, ENT_QUOTES, _CHARSET).'" title="Move comment to Ham category and update spam filter">Ham</a>'; 
				}

				if($modcategory <> 'S')
				{
					$url = $manager->addTicketToUrl($CONF['ActionURL'].'?action=plugin&name=lmcommentmoderator&type=spamcomment&commentid='.$commentid);
					echo ' <a href="'.htmlspecialchars($url, ENT_QUOTES, _CHARSET).'" title="Move comment to Spam category and update spam filter">Spam</a>'; 
				}				
			}
		}
	}

	function doTemplateCommentsVar_cookiewarning($item, $comment, $templatename, $aTemplateCommentsVarParm)
	{
		global $manager, $member, $CONF;
		
		if(!$member->isLoggedIn())
		{
			if($this->aUser)
			{
				$commentid = $comment['commentid'];
				$lastcommentid = $this->aUser['lastcommentid'];
				
				if($lastcommentid == $commentid && $this->_canEdit($commentid))
				{
					$url = $manager->addTicketToUrl($CONF['ActionURL'].'?action=plugin&name=lmcommentmoderator&type=removeuserkey&commentid='.$commentid);

					$cookiewarningtemplate = $this->_getCookieWarningTemplate($templatename);
					
					$aCookiewarning = array(
						'removecookieurl' => $url
					);

					echo TEMPLATE::fill($cookiewarningtemplate, $aCookiewarning);
				}
			}
		}
	}
	
	////////////////////////////////////////////////////////////
	//  doAction functions
	function doAction($actionType)
	{
		global $member, $manager;
		
		if($manager->checkTicket())
		{
			switch (strtoupper($actionType))
			{
				case 'EDITCOMMENT':
					$error = $this->doAction_editcomment();
					break;
				case 'REMOVEUSERKEY':
					$error = $this->doAction_removeuserkey();
					break;
				default:
					if($member->isLoggedIn())
					{
						switch (strtoupper($actionType))
						{
							case 'SHOWCOMMENT':
								$error = $this->doAction_modcategory('O');
								break;
							case 'HIDECOMMENT':
								$error = $this->doAction_modcategory('I');
								break;
							case 'HAMCOMMENT':
								$error = $this->doAction_modcategory('H');
								break;
							case 'SPAMCOMMENT':
								$error = $this->doAction_modcategory('S');
								break;
							default:
								$error = "Unknown actiontype: ".$actionType;
						}
					}
					else
					{
						$error = 'You must be logged to do this action.';
					}
			}
		}
		else
		{
			$error = 'Bad ticket.';
		}
		
		return $error;
	}

	function doAction_modcategory($modcategory)
	{
		global $member;
		
		$commentidstr = requestVar('commentid');
		$aCommentId = explode('-', $commentidstr);

		foreach($aCommentId as $commentid)
		{
			$commentid = intVal($commentid);

			if($commentid)
			{
				$aComment = $this->_getCommentByCommentId($commentid);
				$blogid = $aComment['blogid'];

				if($aComment)
				{
					$aComment = $aComment['0'];
					
					if($member->blogAdminRights($blogid))
					{
						if($this->_updateCommentModSetModCategory($commentid, $modcategory))
						{
							if($modcategory == 'H' || $modcategory == 'S')
							{
								if($this->_performSpamMark($commentid) === false)
								{
									return 'Comment '.$commentid.' SpamMark failed.';
								}
							}

							if($modcategory == 'S' || $modcategory == 'I')
							{
								$urlextra .= 'commentshow='.$commentid;
							}
							else
							{
								$urlextra = '';
							}
							
							$commenturl = $this->_createItemUrl($aComment['itemid'], $urlextra, $commentid);
							
							redirect($commenturl);
						}
						else
						{
							return 'Comment '.$commentid.' category update failed.';
						}
					}
					else
					{
						return 'You do not have admin rights for blogid '.$blogid.'.';
					}
				}
			}
		}
	}

	function doAction_editcomment()
	{
		$commentid = intPostVar('editcommentid');
		$user = postVar('user');
		$userid = postVar('userid');
		$email = postVar('email');
		$body = postVar('body');

		if(!$commentid)
		{
			return "Missing comment id";
		}
		
		$aOldComment = $this->_getCommentByCommentId($commentid);
		if($aOldComment == false)
		{
			return "Error fetching comment from database";
		}
		
		$aOldComment = $aOldComment['0'];

		$redirecturl = '';
		
		$error = $this->_editComment($aOldComment, $user, $userid, $email, $body, $redirecturl);

		if($error == false)
		{
			redirect($redirecturl);
		}
		
		return array('message' => $error);
	}
	
	function doAction_removeuserkey()
	{
		global $CONF;
		
		$commentid = intRequestVar('commentid');

		if($commentid)
		{
			$aComment = $this->_getCommentByCommentId($commentid);
		
			if($aComment)
			{
				$aComment = $aComment['0'];
				
				$itemid = $aComment['itemid'];

				$lifetime = time() - 3600;
				setcookie($CONF['CookiePrefix'] . 'lmcommentmoderator_userkey', '', $lifetime, '/', '', 0);

				$commenturl = $this->_createItemUrl($itemid, '', null).'#nucleus_cf';
					
				redirect($commenturl);
			}
		}
	}

	////////////////////////////////////////////////////////////
	//  Private functions

	function &_getReplacementVarsPlugin()
	{
		global $manager;
		
		$oReplacementVarsPlugin =& $manager->getPlugin('NP_LMReplacementVars');

		if(!$oReplacementVarsPlugin)
		{
			// Panic
			echo '<p>Couldn\'t get plugin LMReplacementVars. This plugin must be installed for the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin to work.</p>';
			return false;
		}
		
		return $oReplacementVarsPlugin;
	}

	function _initializeCommentMod()
	{
		$aComments = $this->_getCommentAll();

		if($aComments === false) { return false; }
		
		foreach($aComments as $aComment)
		{
			$commentid = $aComment['commentid'];
		
			$exists = $this->_getCommentModByCommentId($commentid);
			
			if(!$exists)
			{
				$ret = $this->_insertCommentMod($commentid, null, 'O', '', '', '', '');
				if($ret === false) { return false; }
			}
		}
	}

	function _formModCategory($modcategory, $selectclass)
	{
		if($selectclass)
		{
			$genbootstraphtml = $this->GetOption('genbootstraphtml');

			if($genbootstraphtml == 'yes')
			{
				$selectclass = 'form-control';
			}

			$selectinsert = 'class="'.$selectclass.'" ';
		}
		else
		{
			$genbootstraphtml = 'no';
			$selectinsert = '';
		}
		
		if($genbootstraphtml == 'yes')
		{
			echo '<div class="form-group">';
		}
		
		echo '<label for="plug_lmcommentmoderator_modcategory">Moderation category:</label> ';
		echo'<select '.$selectinsert.'id="plug_lmcommentmoderator_modcategory" name="plug_lmcommentmoderator_modcategory">';
		$this->_selectOption('O', 'Show', $modcategory);
		$this->_selectOption('I', 'Hide', $modcategory);
		$this->_selectOption('H', 'Ham', $modcategory);
		$this->_selectOption('S', 'Spam', $modcategory);
		$this->_selectOption('M', 'Manual Moderation', $modcategory);
		$this->_selectOption('E', 'Members Only', $modcategory);
		echo '</select>';

		if($genbootstraphtml == 'yes')
		{
			echo '</div>';
		}
	}
	
	function _selectOption($value, $description, $selected)
	{
		echo '<option value="'.$value.'"';
		if($value == $selected)
		{
			echo ' selected="selected"';
		}
		echo '>'.$description.'</option>';
	}

	function _genUserKey()
	{
		do
		{
			$userkey = md5(uniqid(rand(), true));
			
			$aUser = $this->_getUserByUserKey($userkey);
			if($aUser === false) { return false; }			
		} while($aUser);
		
		return $userkey;
	}

	function _editComment($aOldComment, $user, $userid, $email, $body, &$redirecturl)
	{
		global $CONF, $manager, $member;
		
		$commentid = $aOldComment['commentid'];
		$blogid = $aOldComment['blogid'];
		$memberid = $aOldComment['memberid'];

		$aCommentMod = $this->_getCommentModByCommentId($commentid);
		if(!$aCommentMod) { return false; }
		$aCommentMod = $aCommentMod['0'];
		
		$oldmodcategory = $aCommentMod['modcategory'];

		if(!$this->_canEdit($commentid))
		{
			return "You don't have the access rights to edit this comment.";
		}
		
		if($memberid)
		{
			$user = '';
			$userid = '';
			$email = '';
		}

		$settings =& $manager->getBlog($blogid);
		$settings->readSettings();

		// begin if: comments disabled
		if(!$settings->commentsEnabled())
		{
			return _ERROR_COMMENTS_DISABLED;
		} // end if

		// begin if: public cannot comment
		if(!$settings->isPublic() && !$memberid)
		{
			return _ERROR_COMMENTS_NONPUBLIC;
		} // end if

		// begin if: comment uses a protected member name
		if($CONF['ProtectMemNames'] && !$memberid && MEMBER::isNameProtected($user))
		{
			return _ERROR_COMMENTS_MEMBERNICK;
		} // end if

		// begin if: email required, but missing (doesn't apply to members)
		if ($settings->emailRequired() && strlen($email) == 0 && !$memberid)
		{
			return _ERROR_EMAIL_REQUIRED;
		} // end if

		// begin if: commenter's name is too long
		if(mb_strlen($user) > 40)
		{
			return _ERROR_USER_TOO_LONG;
		} // end if

		// begin if: commenter's email is too long
		if(mb_strlen($email) > 100)
		{
			return _ERROR_EMAIL_TOO_LONG;
		} // end if

		// begin if: commenter's url is too long
		if(mb_strlen($userid) > 100)
		{
			return _ERROR_URL_TOO_LONG;
		} // end if

		// don't allow words that are too long
		if(preg_match('/[a-zA-Z0-9|\.,;:!\?=\/\\\\]{90,90}/', $body) != 0)
		{
			return _ERROR_COMMENT_LONGWORD;
		}

		if (strlen($body) < 3)
		{
			return _ERROR_COMMENT_NOCOMMENT;
		}

		if (strlen($body) > 5000)
		{
			return _ERROR_COMMENT_TOOLONG;
		}

		if(!$memberid)
		{
			if(strlen($user) < 2)
			{
				return _ERROR_COMMENT_NOUSERNAME;
			}
		}

		if((strlen($email) != 0) && !(isValidMailAddress(trim($email))))
		{
			return _ERROR_BADMAILADDRESS;
		}

		$comment = array('commentid' => $commentid, 'user' => $user, 'userid' => $userid, 'email' => $email, 'body' => $body);

		$error = false;
		$eventdata = array('type' => 'commentedit', 'comment' => &$comment, 'error' => &$error);
		$manager->notify('LMCommentModerator_ValidateForm', $eventdata);

		if($error !== false)
		{
			return $error;
		}

		$comment = COMMENT::prepare($comment);

		$eventdata = array('comment' => &$comment);
        $manager->notify('LMCommentModerator_PreUpdateComment', $eventdata);
		
		$ret = $this->_updateCommentFromEdit($commentid, $comment['user'], $comment['userid'], $comment['email'], $comment['body']);
		if($ret === false)
		{
			return "Update of comment failed in the database";
		}

		$modcategory = '';
		$commenturlextra = '';
		
		if($member->isLoggedIn())
		{
			$editmemberid = $member->getID();

			if($member->blogAdminRights($blogid))
			{
				$modcategory = requestVar('plug_lmcommentmoderator_modcategory');
				
				if($modcategory == 'S' || $modcategory == 'I')
				{
					$commenturlextra = 'commentshow='.$commentid;
				}

				if(!$modcategory)
				{
					$modcategory = 'O';
				}
			} 
			elseif($this->getOption('spamcheckmembers') == 'no')
			{
				$modcategory = $oldmodcategory;
			}
		}
		else
		{
			$editmemberid = null;
		}
		
		$rawbody = postVar('body');

		$ret = $this->_updateCommentModSetModCategoryRawBody($commentid, $modcategory, $rawbody);
		if($ret === false)
		{
			return "Update of comment modcategory failed in the database";
		}
		
		$ret = $this->_updateCommentModSetEdit($commentid, $editmemberid);
		if($ret === false)
		{
			return "Update of comment edit failed in the database";
		}

		if($modcategory)
		{
			if(($modcategory == 'S' || $modcategory == 'H') && $modcategory <> $oldmodcategory)
			{
				if($this->_performSpamMark($commentid) === false)
				{
					return 'Comment '.$commentid.' SpamMark failed.';
				}
			}
		}
		else
		{
			$modcategory = $this->_performSpamCheck($commentid, $oldmodcategory);
			if($modcategory === false)
			{
				return "Spam check returned fail";
			}
		}
		
		$redirecturl = $this->_createItemUrl($aOldComment['itemid'], $commenturlextra, $commentid);

		return false;
	}

	function _createItemUrl($itemid, $extra, $commentid)
	{
		global $catid;
		
		if($catid)
		{
			$linkparams = array('catid' => $catid);
		}
		else
		{
			$linkparams = array();
		}

		$url = createItemLink($itemid, $linkparams);
		
		if($extra)
		{
			if(strpos($url, '?'))
			{
				$url .= '&';
			}
			else
			{
				$url .= '?';
			}
			
			$url .= $extra;
		}
		
		if($commentid)
		{
			$url .= '#comment'.$commentid;
		}
		
		return $url;
	}

	function _canEdit($commentid)
	{
		global $member;
		
		$canedit = false;
				
		if($member->isLoggedIn())
		{
			if($member->canAlterComment($commentid))
			{
				$canedit = true;
			}
		}
		elseif($this->aUser)
		{
			$aCommentMod = $this->_getCommentModByCommentId($commentid);
			if($aCommentMod == false) { return false; }
			$aCommentMod = $aCommentMod['0'];
			
			$userid = $aCommentMod['userid'];
			
			if($userid == $this->aUser['userid'])
			{
				$aComment = $this->_getCommentByCommentId($commentid);
				
				if($aComment == false) { return false; }
				$aComment = $aComment['0'];
				
				$timestamp = strtotime($aComment['ctime']);
				
				if(time() <= ($timestamp + (60 * 60)))
				{
					$canedit = true;
				}
			}
		}
		
		return $canedit;
	}

	function _intValNull($intval)
	{
		if($intval === null)
		{
			$intvalstr = 'null';
		}
		else
		{
			$intvalstr = intVal($intval);
		}
		
		return $intvalstr;
	}

	function _performSpamCheck($commentid, $oldmodcategory)
	{
		global $manager;
		
		$result = '';
		$plugin = '';
		$message = '';
		
		$aComment = $this->_getCommentByCommentId($commentid);
		if(!$aComment) { return false; }
		$aComment = $aComment['0'];

		$aCommentMod = $this->_getCommentModByCommentId($commentid);
		if(!$aCommentMod) { return false; }
		$aCommentMod = $aCommentMod['0'];
		
		$rawbody = $aCommentMod['rawbody'];
		$memberid = $aComment['memberid'];

		if($rawbody)
		{
			$spamcheck = array('type' => 'comment',
					'commentid' => $commentid,
					'itemid' => $aComment['itemid'],
					'memberid' => $memberid,
					'body' => $rawbody,
					'author' => $aComment['user'],
					'email' => $aComment['email'],
					'url' => $aComment['userid'],
					'ip' => $aComment['ip'],
					'result' => &$result,
					'plugin' => &$plugin,
					'message' => &$message
				);
					
			if($memberid)
			{
				$commentmember = MEMBER::createFromID($memberid);
				if(!$commentmember->getID()) { return false; }
				 
				$spamcheck['author'] = $commentmember->getDisplayName();
				$spamcheck['email'] = $commentmember->getEmail();
				$spamcheck['url'] = $commentmember->getURL();
			}

			$eventdata = array('spamcheck' => &$spamcheck);
			$manager->notify('LMCommentModerator_SpamCheck', $eventdata);
		}
		
		if($result && ($result == 'S' || $result == 'H'))
		{
			$modcategory = $result;
			
			$ret = $this->_updateCommentModSetSpamResult($commentid, $result, $plugin, $message);
			if($ret === false) { return false; }
		}
		else
		{
			$modcategory = 'M';

			$ret = $this->_updateCommentModSetSpamResult($commentid, '', '', '');
			if($ret === false) { return false; }
		}
		
		$ret = $this->_updateCommentModSetModCategory($commentid, $modcategory);
		if($ret === false) { return false; }

		if($modcategory <> $oldmodcategory)
		{
			$this->_adminNotification($commentid, $modcategory);
		}
		
		return $modcategory;
	}
	
	function _performSpamMark($commentid)
	{
		global $manager, $member;
		
		$aComment = $this->_getCommentByCommentId($commentid);
		if(!$aComment) { return false; }
		$aComment = $aComment['0'];

		$aCommentMod = $this->_getCommentModByCommentId($commentid);
		if(!$aCommentMod) { return false; }
		$aCommentMod = $aCommentMod['0'];
		
		$rawbody = $aCommentMod['rawbody'];
		$modcategory = $aCommentMod['modcategory'];
		$memberid = $aComment['memberid'];

		if($rawbody && ($modcategory == 'H' || $modcategory == 'S'))
		{
			$spammark = array('type' => 'comment',
					'commentid' => $commentid,
					'itemid' => $aComment['itemid'],
					'memberid' => $memberid,
					'body' => $rawbody,
					'author' => $aComment['user'],
					'email' => $aComment['email'],
					'url' => $aComment['userid'],
					'ip' => $aComment['ip'],
					'result' => $modcategory
				);
					
			if($memberid)
			{
				$commentmember = MEMBER::createFromID($memberid);
				if(!$commentmember->getID()) { return false; }
				 
				$spammark['author'] = $commentmember->getDisplayName();
				$spammark['email'] = $commentmember->getEmail();
				$spammark['url'] = $commentmember->getURL();
			}

			$eventdata = array('spammark' => &$spammark);
			$manager->notify('LMCommentModerator_SpamMark', $eventdata);
			
			$message = "Comment marked as ".$this->_commentModGetModCategoryName($modcategory)." by moderator (".$member->getDisplayName().')';
			
			$ret = $this->_updateCommentModSetSpamResult($commentid, $modcategory, $this->getName(), $message);
			if($ret === false) { return false; }
		}
	}
	
	function _addToFilter($type, $filter, $modcategory)
	{
		$aFilter = $this->_getFilterByTypeFilter($type, $filter);
		if($aFilter === false) { return false; }
		
		if($aFilter)
		{
			$aFilter = $aFilter['0'];
			$filterid = $aFilter['filterid'];
			$status = $aFilter['status'];
			
			if(($aFilter['modcategory'] != $modcategory) && ($status != 'L') && ($status != 'D'))
			{
				$ret = $this->_updateFilterSetModCategory($filterid, $modcategory);
				if($ret === false) { return false; }
			}
		}
		else
		{
			$ret = $this->_insertFilter($type, $filter, $modcategory, '');
			if($ret === false) { return false; }
		}
	}
	
	function _checkFilter($type, $filter)
	{
		$modcategory = false;
		
		$aFilter = $this->_getFilterByTypeFilter($type, $filter);
		if($aFilter === false) { return false; }
		
		if($aFilter)
		{
			$aFilter = $aFilter['0'];

			$filterid = $aFilter['filterid'];
			$status = $aFilter['status'];
			
			if($status != 'D') // Disabled
			{
				$modcategory = $aFilter['modcategory'];
		
				$ret = $this->_updateFilterSetLatestUse($filterid);
				if($ret === false) { return false; }
			}
		}

		return $modcategory;
	}

	function _filterGetTypeName($type)
	{
		$typenames = array('M' => 'Member', 'I' => 'IP', 'E' => 'Email', 'W' => 'Website');
		
		if(isset($typenames[$type]))
		{
			$typename = $typenames[$type];
		}
		else
		{
			$typename = 'unknown';
		}
		
		return $typename;
	}

	function _filterGetStatusName($status)
	{
		$statusnames = array('L' => 'Locked', 'D' => 'Disabled');
		
		if(!$status)
		{	
			$statusname = 'Normal';
		}
		elseif(isset($statusnames[$status]))
		{
			$statusname = $statusnames[$status];
		}
		else
		{
			$statusname = 'unknown';
		}
		
		return $statusname;
	}

	function _commentModGetModCategoryName($modcategory)
	{
		$modcategories = array('O' => 'Show', 'I' => 'Hide', 'H' => 'Ham', 'S' => 'Spam', 'M' => 'Moderate', 'E' => 'Members');
		
		if(isset($modcategories[$modcategory]))
		{
			$modcategoryname = $modcategories[$modcategory];
		}
		else
		{
			$modcategoryname = 'unknown';
		}
		
		return $modcategoryname;
	}

	function _showModCommentMetaData($aCommentMod)
	{
		$spamchecktext = $this->_getModCommentSpamCheckText($aCommentMod);

		if($spamchecktext)
		{
			echo '<p><small>'.$spamchecktext.'</small></p>';
		}

		$editwhen = $aCommentMod['editwhen'];
		
		if($editwhen)
		{
			$when = strtotime($editwhen);
			$editwhentext = date('d-M-y', $when).' '.date('H:i', $when);
		}
		else
		{
			$editwhentext = false;
		}
		
		if($editwhentext)
		{
			$editmemberid = $aCommentMod['editmemberid'];

			if($editmemberid)
			{
				$editmember = MEMBER::createFromID($editmemberid);
				$editby = $editmember->getDisplayName();
			}
			else
			{
				$editby = "Author";
			}
			
			echo '<p><small>Last edited '.$editwhentext.' by '.$editby.'.</small></p>';
		}
	}
	
	function _getModCommentSpamCheckText($aCommentMod)
	{
		$result = $aCommentMod['spamcheckresult'];
		$plugin = $aCommentMod['spamcheckplugin'];
		$message = $aCommentMod['spamcheckmessage'];
		$spamcheckwhen = $aCommentMod['spamcheckwhen'];

		if($spamcheckwhen)
		{
			$when = strtotime($spamcheckwhen);
			$spamcheckwhentext = date('d-M-y', $when).' '.date('H:i', $when);
		}
		else
		{
			$spamcheckwhentext = false;
		}

		$spamchecktext = '';
		
		if($result)
		{
			switch($result)
			{
				case 'S':
					$resulttext = 'Spam';
					break;
				case 'H':
					$resulttext = 'Ham';
					break;
				default:
					$resulttext = $result;
					break;
			}
			
			$spamchecktext = 'Spam check: '.$spamcheckwhentext.', result: '.$resulttext.', Plugin: '.$plugin.', Message: '.$message;
		}
		elseif($spamcheckwhentext)
		{
			$spamchecktext = 'Spam check: '.$spamcheckwhentext.', result: Undecided';
		}
		
		return $spamchecktext;
	}

	function _getCommentFormTemplate($type, $templatename)
	{
		global $manager, $DIR_PLUGINS;
		
		$formtemplate = '';
		
		if($templatename)
		{
			$template =& $manager->getTemplate($templatename);

			$templateindex = 'lmcommentmoderator_'.$type;
			
			if(isset($template[$templateindex]))
			{
				$formtemplate = $template[$templateindex];
			}
		}

		if(!$formtemplate)
		{
			$filename = $DIR_PLUGINS.'lmcommentmoderator/'.$type.'.template';
			
			if(file_exists($filename)) 
			{
				$formtemplate = file_get_contents($filename);
			}
		}
		
		return $formtemplate;
	}
	
	function _getModCategoryTemplate($modcategory, $templatename)
	{
		global $manager;
		
		$modcategorytemplate = '';
		
		if($templatename)
		{
			$template =& $manager->getTemplate($templatename);

			$templateindex = 'lmcommentmoderator_modcategory_'.strtolower($this->_commentModGetModCategoryName($modcategory));
			
			if(isset($template[$templateindex]))
			{
				$modcategorytemplate = $template[$templateindex];
			}
		}

		if(!$modcategorytemplate)
		{
			switch ($modcategory)
			{
				case 'O':  // Show
					$modcategorytemplate = '';
					break;

				case 'I': // Hide
					$modcategorytemplate = '<div class="itemcommentmodcategory"><p>This comment has been marked as hidden. It will not be publicly available.</p></div>';
					break;
					
				case 'H': // Ham
					$modcategorytemplate = '';
					break;
			
				case 'S': // Spam
					$modcategorytemplate = '<div class="itemcommentmodcategory"><p>This comment has been categorized as spam. It will not be publicly available.</p></div>';
					break;

				case 'M': // Moderate
					$modcategorytemplate = '<div class="itemcommentmodcategory"><p>This comment is in the moderation queue. It must be approved before it can be publicly available.</p></div>';
					break;

				case 'E': // Member
					$modcategorytemplate = '<div class="itemcommentmodcategory"><p>This comment is available only for members.</p></div>';
					break;
					
				default:
					$modcategorytemplate = 'Unknown ModCategory';
					break;
			}
		}
		
		return $modcategorytemplate;
	}
	
	function _getCookieWarningTemplate($templatename)
	{
		global $manager;
		
		$cookiewarningtemplate = '';
		
		if($templatename)
		{
			$template =& $manager->getTemplate($templatename);

			$templateindex = 'lmcommentmoderator_cookiewarning';
			
			if(isset($template[$templateindex]))
			{
				$cookiewarningtemplate = $template[$templateindex];
			}
		}

		if(!$cookiewarningtemplate)
		{
			$cookiewarningtemplate = '<div class="itemcommentcookiewarning"><p>You can edit your comments until 1 hour after they was added. '."\n"
					.'For you to be able to do this is an identification cookie stored in your browser. '."\n"
					.'The identification cookie is also used to fill out the add comment form and show the moderation status of your comments. '."\n"
					.'If you are on a public computer you should <a href="<%removecookieurl%>" title="Remove identification cookie">remove the cookie</a>.'."\n"
					.'</p></div>';
		}
		
		return $cookiewarningtemplate;
	}

	function _adminNotification($commentid, $modcategory)
	{
		global $CONF;
		
		$notificationemail = $this->GetOption('notificationemail');
		
		if($notificationemail)
		{
			$notificationham = $this->GetOption('notificationham');
			$notificationmoderate = $this->GetOption('notificationmoderate');
			$notificationspam = $this->GetOption('notificationspam');
			
			if(($notificationmoderate == 'yes' && $modcategory == 'M') 
				|| ($notificationham == 'yes' && $modcategory == 'H') 
				|| ($notificationspam == 'yes' && $modcategory == 'S'))
			{
				$aComment = $this->_getCommentJoinModByCommentID($commentid);
				if(!$aComment) { return false; }
				
				$aComment = $aComment['0'];
				
				$edited = $aComment['editwhen'];
				
				if($modcategory == 'M' || !$edited)
				{
					$body = $aComment['body'];
					$itemid = $aComment['itemid'];
					$itemtitle = $aComment['itemtitle'];
					$http = $aComment['userid'];
					$membername = $aComment['membername'];

					$aCommentMod = $this->_getCommentModByCommentId($commentid);
					if(!$aCommentMod) { return false; }
					
					$aCommentMod = $aCommentMod['0'];
					
					if($membername)
					{
						$user = $membername.' (member)';
					}
					else
					{
						$user = $aComment['user'].' (email: '.$aComment['email'].', website: '.$aComment['userid'].')';
					}

					$from = $CONF['AdminEmail'];
					
					if($modcategory == 'S' || $modcategory == 'I')
					{
						$urlextra .= 'commentshow='.$commentid;
					}
					else
					{
						$urlextra = '';
					}

					$body = str_replace('<br />', '', $body);
					$body = preg_replace("#<a href=['\"]([^'\"]+)['\"]( rel=\"nofollow\")?>[^<]*</a>#i", "\\1", $body);

					$commenturl = $this->_createItemUrl($itemid, $urlextra, $commentid);
					$pluginurl = $this->getAdminURL();

					$headers = 'From: '.$from."\n"
							.'X-Mailer: PHP/'.phpversion()."\n"
							.'Return-Path: '.$from."\n"
							.'Content-type: text/plain; charset=iso-8859-1'."\n";
							
					$adminpage = 'Plugin admin page: '.$pluginurl."\n";
					$showspamcheck = false;
					
					switch($modcategory)
					{
						case 'M':
							$subject = 'Comment awaiting moderation'; 
							$message = 'A comment has been added to the moderation queue.'."\n\n";
							$adminpage = 'Moderation queue: '.$pluginurl."\n";
							break;
						case 'S':
							$subject = "New comment classified as Spam"; 
							$message = 'A new comment has been classified as Spam.'."\n";
							$showspamcheck = true;
							break;
						case 'H':
							$subject = "New comment classified as Ham"; 
							$message = 'A new comment has been classified as Ham.'."\n";
							$showspamcheck = true;
							break;
						default:
							$subject = "LMCommentModerator Notification";
							$message = "Unknown classification: ".$modcategory;
							break;
					}

					if($showspamcheck)
					{
						$spamchecktext = $this->_getModCommentSpamCheckText($aCommentMod);
						
						if($spamchecktext)
						{
							$spamchecktext .= "\n";
						}
					}
					else
					{
						$spamchecktext = '';
					}

					$message .= 'Direct comment link: '.$commenturl."\n"
						.$adminpage
						.$spamchecktext."\n"
						.'User: '.$user."\n"
						.'Item: '.$itemtitle."\n\n"
						.'Comment: '."\n"
						.$body;

					$return = mail($notificationemail, $subject, $message, $headers);					
				}
			}
		}
	}

	function _purgeSpam()
	{
		$purgespamafterdays =  IntVal($this->getOption('purgespamafterdays'));
		
		if($purgespamafterdays > 0)
		{
			$aComments = $this->_getCommentForSpamPurge($purgespamafterdays);	
			if($aComments === false) { return false; }
		
			foreach($aComments as $aComment)
			{
				$commentid = $aComment['commentid'];
			
				$ret = $this->_deleteOneComment($commentid);
				if(ret === false) { return false; }
			}
		}
	}
	
    function _deleteOneComment($commentid) 
	{
		// Want to be able to purge spam comments without a logged in administrator. Spam purge is to be triggered when new comments are classified as spam.
		// Have to duplicate this method from the admin object as the version in the admin object requires a member with comment modification access.
        global $manager;

        $commentid = intval($commentid);

        $data = array('commentid' => $commentid);
        $manager->notify('PreDeleteComment', $data);

        $query = 'DELETE FROM '.sql_table('comment').' WHERE cnumber=' . $commentid;
        sql_query($query);

        $data = array('commentid' => $commentid);
        $manager->notify('PostDeleteComment', $data);
		
		return true;
    }

	/////////////////////////////////////////////////////
	// Data access and manipulation functions

	/////////////////////////////////////////////////////////
	// Data access functions on Comments
	function _getCommentAll()
	{
		return $this->_getComment(0, 0);
	}
	
	function _getCommentByCommentId($commentid)
	{
		return $this->_getComment($commentid, 0);
	}
	
	function _getComment($commentid, $itemid)
	{
		$ret = array();
		
		$query = 'SELECT c.citem as itemid, c.cnumber as commentid, c.cbody as body, c.cuser as user, c.cmail as userid, '
				.'c.cemail as email, c.cmember as memberid, c.ctime, c.chost as host, c.cip as ip, c.cblog as blogid '
				.'FROM '.sql_table('comment').' as c ';
		
		if($commentid)
		{
			$query .= "WHERE  c.cnumber = ".$commentid." ";
		}
		elseif($itemid)
		{
			$query .= "WHERE c.citem = ".$itemid." ";
		}

		$res = sql_query($query);
		
		if($res)
		{			
			while ($comment = sql_fetch_assoc($res)) 
			{
				array_push($ret, $comment);
			}
		}
		else
		{
			return false;
		}

		return $ret;
	}

	function _getCommentJoinModByCommentID($commentid)
	{
		return $this->_getCommentJoinMod($commentid, 0, 0, 0);
	}

	function _getCommentJoinModByABlogIdModCategory($ablogid, $modcategory)
	{
		return $this->_getCommentJoinMod(0, 0, $ablogid, $modcategory);
	}
	
	function _getCommentJoinMod($commentid, $itemid, $ablogid, $modcategory)
	{
		$ret = array();
		
		$query = "SELECT c.citem as itemid, c.cnumber as commentid, c.cbody as body, c.cuser as user, c.cmail as userid, "
				."c.cemail as email, c.cmember as memberid, c.ctime, c.chost as host, c.cip as ip, c.cblog as blogid, "
				."lmcm.modcategory as modcategory, i.ititle as itemtitle, "
				."(SELECT mname FROM ".sql_table('member')." as m WHERE m.mnumber = c.cmember) as membername, "
				."lmcm.editwhen as editwhen "
				."FROM ".sql_table('comment')." as c, ".$this->getTableCommentMod()." as lmcm, ".sql_table('item')." as i "
				."WHERE c.cnumber = lmcm.commentid "
				." AND c.citem = i.inumber ";
		
		if($commentid)
		{
			$query .= "AND c.cnumber = ".intVal($commentid)." ";
		}

		if($itemid)
		{
			$query .= "AND c.citem = ".intVal($itemid)." ";
		}
		
		if($ablogid)
		{
			$instr = '';
			
			foreach($ablogid as $blogid)
			{
				if($instr)
				{
					$instr .= ' ,';
				}
				
				$instr .= intVal($blogid);
			}
			
			$query .= "AND c.cblog IN (".$instr.") ";
		}
		
		if($modcategory)
		{
			$query .= "AND lmcm.modcategory = '".sql_real_escape_string($modcategory)."' ";
		}
		
		$query .= 'ORDER BY c.cnumber';

		$res = sql_query($query);
		
		if($res)
		{			
			while ($comment = sql_fetch_assoc($res)) 
			{
				array_push($ret, $comment);
			}
		}
		else
		{
			return false;
		}

		return $ret;
	}

	function _getCommentForSpamPurge($purgespamafterdays)
	{
		$purgespamafterdays = IntVal($purgespamafterdays);
		
		$ret = array();
		
		if($purgespamafterdays > 0)
		{
			$query = "SELECT c.cnumber as commentid "
					."FROM ".sql_table('comment')." as c, ".$this->getTableCommentMod()." as lmcm "
					."WHERE c.cnumber = lmcm.commentid "
					."AND lmcm.modcategory = 'S' "
					."AND c.ctime < (now() - interval ".$purgespamafterdays." day)"
					."ORDER BY c.ctime";

			$res = sql_query($query);
			
			if($res)
			{			
				while ($comment = sql_fetch_assoc($res)) 
				{
					array_push($ret, $comment);
				}
			}
			else
			{
				return false;
			}
		}
		
		return $ret;
	}

	function _updateCommentFromEdit($commentid, $user, $userid, $email, $body)
	{
		$query = "UPDATE ".sql_table('comment')." SET cuser = '".sql_real_escape_string($user)."', cmail = '".sql_real_escape_string($userid)."', "
				."cemail = '".sql_real_escape_string($email)."', cbody = '".sql_real_escape_string($body)."' "
				."WHERE cnumber = ".intVal($commentid);
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}

	/////////////////////////////////////////////////////////
	// Data access functions on CommentMod
	
	function _getCommentModByCommentId($commentid)
	{
		return $this->_getCommentMod($commentid);
	}

	function _getCommentMod($commentid)
	{
		$ret = array();
		
		$query = "SELECT commentid, userid, modcategory, rawbody, spamcheckresult, spamcheckplugin, spamcheckmessage, spamcheckwhen, ";
		$query .= "editwhen, editmemberid  FROM ".$this->getTableCommentMod()." ";
		
		if($commentid)
		{
			$query .= "WHERE commentid = ".$commentid." ";
		}

		$res = sql_query($query);
		
		if($res)
		{			
			while ($comment = sql_fetch_assoc($res)) 
			{
				array_push($ret, $comment);
			}
		}
		else
		{
			return false;
		}
		return $ret;
	}
	
	function _insertCommentMod($commentid, $userid, $modcategory, $rawbody, $spamcheckresult, $spamcheckplugin, $spamcheckmessage)
	{
		$query = "INSERT ".$this->getTableCommentMod()." (commentid, userid, modcategory, rawbody, spamcheckresult, spamcheckplugin, spamcheckmessage, editwhen, editmemberid) "
				."VALUES (".intVal($commentid).", ".$this->_intValNull($userid).", '".sql_real_escape_string($modcategory)."', '".sql_real_escape_string($rawbody)."', "
					."'".sql_real_escape_string($spamcheckresult)."', '".sql_real_escape_string($spamcheckplugin)."', '".sql_real_escape_string($spamcheckmessage)."', NULL, NULL)";
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}

	function _updateCommentMod($commentid, $userid, $modcategory, $rawbody, $spamcheckresult, $spamcheckplugin, $spamcheckmessage)
	{
		$query = "UPDATE ".$this->getTableCommentMod()." SET userid = ".$this->_intValNull($userid).", modcategory = '".sql_real_escape_string($modcategory)."', rawbody = '".sql_real_escape_string($rawbody)."', "
					."spamcheckresult = '".sql_real_escape_string($spamcheckresult)."', spamcheckplugin = '".sql_real_escape_string($spamcheckplugin)."', spamcheckmessage = '".sql_real_escape_string($spamcheckmessage)."' "
				."WHERE commentid = ".intVal($commentid);
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}

	function _updateCommentModSetModCategory($commentid, $modcategory)
	{
		$query = "UPDATE ".$this->getTableCommentMod()." SET modcategory = '".sql_real_escape_string($modcategory)."' "
				."WHERE commentid = ".intVal($commentid);
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}
	
	function _updateCommentModSetModCategoryRawBody($commentid, $modcategory, $rawbody)
	{
		$query = "UPDATE ".$this->getTableCommentMod()." SET modcategory = '".sql_real_escape_string($modcategory)."', rawbody = '".sql_real_escape_string($rawbody)."' "
				."WHERE commentid = ".intVal($commentid);
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}
		
	function _updateCommentModSetSpamResult($commentid, $spamcheckresult, $spamcheckplugin, $spamcheckmessage)
	{
		$query = "UPDATE ".$this->getTableCommentMod()." SET "
					."spamcheckresult = '".sql_real_escape_string($spamcheckresult)."', "
					."spamcheckplugin = '".sql_real_escape_string($spamcheckplugin)."', "
					."spamcheckmessage = '".sql_real_escape_string($spamcheckmessage)."', "
					."spamcheckwhen = now() "
				."WHERE commentid = ".intVal($commentid);
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}
	
	function _updateCommentModSetEdit($commentid, $editmemberid)
	{
		$query = "UPDATE ".$this->getTableCommentMod()." SET "
					."editmemberid = ".$this->_intValNull($editmemberid).", "
					."editwhen = now() "
				."WHERE commentid = ".intVal($commentid);
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}
	
	function _deleteCommentMod($commentid)
	{
		$query = "DELETE FROM ".$this->getTableCommentMod()." ";
		
		if($commentid)
		{
			$query .= "WHERE commentid = ".intVal($commentid)." ";
		}
		else
		{
			return false;
		}
	
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		return true;
	}

	/////////////////////////////////////////////////////////
	// Data access functions on User

	function _getUserByUserId($userid)
	{
		return $this->_getUser($userid, false, false);
	}
	
	function _getUserByEMail($email)
	{
		return $this->_getUser(false, $email, false);
	}

	function _getUserByUserKey($userkey)
	{
		return $this->_getUser(false, false, $userkey);
	}

	function _getUser($userid, $email, $userkey)
	{
		$ret = array();
		
		$query = "SELECT userid, username, email, website, userkey, created, lastused, usecount, lastcommentid FROM ".$this->getTableUser()." ";
		
		if($userid)
		{
			$query .= "WHERE userid = ".intVal($userid)." ";
		}
		elseif($email)
		{
			$query .= "WHERE email = '".sql_real_escape_string($email)."' ";
		}
		elseif($userkey)
		{
			$query .= "WHERE userkey = '".sql_real_escape_string($userkey)."' ";
		}

		$res = sql_query($query);
		
		if($res)
		{			
			while ($comment = sql_fetch_assoc($res)) 
			{
				array_push($ret, $comment);
			}
		}
		else
		{
			return false;
		}

		return $ret;
	}
	
	function _insertUser($username, $email, $website, $userkey, $lastcommentid)
	{
		$query = "INSERT ".$this->getTableUser()." (username, email, website, userkey, created, lastused, usecount, lastcommentid) "
				."VALUES ('".sql_real_escape_string($username)."', '".sql_real_escape_string($email)."', '".sql_real_escape_string($website)
				."', '".sql_real_escape_string($userkey)."', now(), now(), 0, ".$this->_intValNull($lastcommentid).")";
					
		$res = sql_query($query);
		
		if(!$res)
		{
			return false;
		}
		
		$userid = sql_insert_id();
		
		return $userid;
	}

	function _updateUserSetLatestUse($userid)
	{
		$query = "UPDATE ".$this->getTableUser()." SET "
				."usecount = usecount + 1, "
				."lastused =  now() "
				."WHERE userid = ".intVal($userid)." ";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	function _updateUserSetLastCommentMeta($userid, $username, $email, $website)
	{
		$query = "UPDATE ".$this->getTableUser()." SET "
				."username = '".sql_real_escape_string($username)."', "
				."email = '".sql_real_escape_string($email)."', "
				."website = '".sql_real_escape_string($website)."' "
				."WHERE userid = ".intVal($userid)." ";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	function _updateUserSetLastCommentId($userid, $lastcommentid)
	{
		$query = "UPDATE ".$this->getTableUser()." SET "
				."lastcommentid = ".$this->_intValNull($lastcommentid)." "
				."WHERE userid = ".intVal($userid)." ";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	function _deleteUser($userid)
	{
		$query = "DELETE FROM ".$this->getTableUser()." ";
		
		if($userid)
		{
			$query .= "WHERE userid = ".intVal($userid)." ";
		}
		else
		{
			return false;
		}
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	/////////////////////////////////////////////////////////
	// Data access functions on Filter
	
	function _getFilterByFilterId($filterid)
	{
		return $this->_getFilter($filterid, false, false, false);
	}

	function _getFilterByTypeFilter($type, $filter)
	{
		return $this->_getFilter(false, $type, $filter, false);
	}
	
	function _getFilterByType($type)
	{
		return $this->_getFilter(false, $type, false, 'filter');
	}

	function _getFilter($filterid, $type, $filter, $orderby)
	{
		$ret = array();
		
		$query = "SELECT filterid, type, filter, modcategory, status, created, lastused, usecount FROM ".$this->getTableFilter()." ";
		
		if($filterid)
		{
			$query .= "WHERE filterid = ".intVal($filterid)." ";
		}
		elseif($type && $filter)
		{
			$query .= "WHERE type = '".sql_real_escape_string($type)."' ";
			$query .= "AND filter = '".sql_real_escape_string($filter)."' ";
		}
		elseif($type)
		{
			$query .= "WHERE type = '".sql_real_escape_string($type)."' ";
		}

		if($orderby)
		{
			$query .= "ORDER BY ".$orderby." ";
		}
		
		$res = sql_query($query);
		
		if($res)
		{			
			while ($comment = sql_fetch_assoc($res)) 
			{
				array_push($ret, $comment);
			}
		}
		else
		{
			return false;
		}
		return $ret;
	}
	
	function _insertFilter($type, $filter, $modcategory, $status)
	{
		$query = "INSERT ".$this->getTableFilter()." (type, filter, modcategory, status, created, lastused, usecount) "
				."VALUES ('".sql_real_escape_string($type)."', '".sql_real_escape_string($filter)."', '".sql_real_escape_string($modcategory)
				."', '".sql_real_escape_string($status)."', now(), now(), 0)";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
		
		$filterid = sql_insert_id();
		
		return $filterid;
	}

	function _updateFilterSetLatestUse($filterid)
	{
		$query = "UPDATE ".$this->getTableFilter()." SET "
				."usecount = usecount + 1, "
				."lastused =  now() "
				."WHERE filterid = ".intVal($filterid)." ";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	function _updateFilterSetModCategory($filterid, $modcategory)
	{
		$query = "UPDATE ".$this->getTableFilter()." SET "
				."modcategory = '".sql_real_escape_string($modcategory)."' "
				."WHERE filterid = ".intVal($filterid)." ";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}
	
	function _updateFilterSetModCategoryStatus($filterid, $modcategory, $status)
	{
		$query = "UPDATE ".$this->getTableFilter()." SET "
				."modcategory = '".sql_real_escape_string($modcategory)."', "
				."status = '".sql_real_escape_string($status)."' "
				."WHERE filterid = ".intVal($filterid)." ";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	function _updateFilterSetStatus($filterid, $status)
	{
		$query = "UPDATE ".$this->getTableFilter()." SET "
				."status = '".sql_real_escape_string($status)."' "
				."WHERE filterid = ".intVal($filterid)." ";
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	function _deleteFilter($filterid)
	{
		$query = "DELETE FROM ".$this->getTableFilter()." ";
		
		if($filterid)
		{
			$query .= "WHERE filterid = ".intVal($filterid)." ";
		}
		else
		{
			return false;
		}
					
		$res = sql_query($query);
		
		if(!$res) { return false; }
				
		return true;
	}

	////////////////////////////////////////////////////////////////////////
	// Plugin Upgrade handling functions
	function getCurrentDataVersion()
	{
		$currentdataversion = $this->getOption('currentdataversion');
		
		if(!$currentdataversion)
		{
			$currentdataversion = 0;
		}
		
		return $currentdataversion;
	}

	function setCurrentDataVersion($currentdataversion)
	{
		$res = $this->setOption('currentdataversion', $currentdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getCommitDataVersion()
	{
		$commitdataversion = $this->getOption('commitdataversion');
		
		if(!$commitdataversion)
		{
			$commitdataversion = 0;
		}

		return $commitdataversion;
	}

	function setCommitDataVersion($commitdataversion)
	{	
		$res = $this->setOption('commitdataversion', $commitdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getDataVersion()
	{
		return 3;
	}
	
	function upgradeDataTest($fromdataversion, $todataversion)
	{
		// returns true if rollback will be possible after upgrade
		$res = true;
				
		return $res;
	}
	
	function upgradeDataPerform($fromdataversion, $todataversion)
	{
		// Returns true if upgrade was successfull
		
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
					$this->createOption('currentdataversion', 'currentdataversion', 'text','0', 'access=hidden');
					$this->createOption('commitdataversion', 'commitdataversion', 'text','0', 'access=hidden');

					$this->createOption('del_uninstall', 'Delete NP_LMCommentModerator data tables on uninstall?', 'yesno','no');
					$this->createOption('spamchecktestmode', 'Spam check test modus?', 'yesno','no');

					$this->createOption('spamcheckmembers', 'Spam check member comments?', 'yesno','no');
					$this->createOption('filtermembers', 'Enable member filter?', 'yesno','no');
					$this->createOption('filterip', 'Enable IP filter?', 'yesno','yes');
					$this->createOption('filteremail', 'Enable EMail filter?', 'yesno','yes');
					$this->createOption('filterwebsite', 'Enable Website filter?', 'yesno','yes');
					$this->createOption('notificationemail', 'Email address for admin notification', 'text','');
					$this->createOption('notificationham', 'Send notification on new Ham comments?', 'yesno','yes');
					$this->createOption('notificationmoderate', 'Send notification on comments added to the moderation queue?', 'yesno','yes');
					$this->createOption('notificationspam', 'Send notification on new Spam comments?', 'yesno','no');

					$this->_createTableUser();					
					$this->_createTableCommentMod();
					$this->_createTableFilter();
					
					$this->_initializeCommentMod();
						
					$res = true;
					break;
				case 2:
					$this->createOption('purgespamafterdays', 'Purge Spam comments after X days', 'text', '0', 'datatype=numerical');
					$res = true;
					break;
				case 3:
					$this->createOption('genbootstraphtml', 'Generate Bootstrap HTML?', 'yesno','no');
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		
		return true;
	}
	
	function upgradeDataRollback($fromdataversion, $todataversion)
	{
		// Returns true if rollback was successfull
		for($ver = $fromdataversion; $ver >= $todataversion; $ver--)
		{
			switch($ver)
			{
				case 1:
					$res = true;
					break;
				case 2:
					$this->deleteOption('purgespamafterdays');
					$res = true;
					break;
				case 3:
					$this->deleteOption('genbootstraphtml');
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}

		return true;
	}

	function upgradeDataCommit($fromdataversion, $todataversion)
	{
		// Returns true if commit was successfull
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
					$res = true;
					break;
				case 2:
					$res = true;
					break;
				case 3:
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		return true;
	}
	
	function _createTableCommentMod()
	{
		$query  = "CREATE TABLE IF NOT EXISTS ".$this->getTableCommentMod();
		$query .= "( ";
		$query .= "commentid int(11) NOT NULL, ";
		$query .= "modcategory char(1) NOT NULL, "; // 'O' - Show, 'I' - Hide, 'H' - Ham, 'S' - Spam, 'M' - Manual Moderation, 'E' - Members only
		$query .= "userid int(11) NULL, ";
		$query .= "rawbody text NOT NULL, ";
		$query .= "spamcheckresult char(1) NOT NULL, "; // 'H' - Ham, 'S' - Spam
		$query .= "spamcheckplugin varchar(40) NOT NULL, ";
		$query .= "spamcheckmessage varchar(255) NOT NULL, ";
		$query .= "spamcheckwhen datetime NULL, ";
		$query .= "editwhen datetime NULL, "; // Last edit
		$query .= "editmemberid int(11) NULL, "; // Memberid who did last edit. NULL if comment was edited by comment author.
		$query .= "PRIMARY KEY (commentid) ";
		$query .= ") ";
		
		sql_query($query);

		if($this->_checkIndexIfExists($this->getTableCommentMod(), 'userid_idx') == 0)
		{
			$query  = "CREATE INDEX userid_idx ON ".$this->getTableCommentMod()." (userid)";
			sql_query($query);
		}
	}

	function _createTableUser()
	{
		$query  = "CREATE TABLE IF NOT EXISTS ".$this->getTableUser();
		$query .= "( ";
		$query .= "userid int(11) NOT NULL auto_increment, ";
		$query .= "username varchar(40) NOT NULL, "; // Last name used in comment
		$query .= "email varchar(100) NOT NULL, "; // Last email used in comment
		$query .= "website varchar(100) NOT NULL, "; // Last website used in comment
		$query .= "userkey char(32) NOT NULL, "; // md5(uniqid(rand(), true));
		$query .= "created datetime NOT NULL, "; 
		$query .= "lastused datetime NOT NULL, "; // Last time the userkey cookie was used when accessing the site
		$query .= "usecount int(11) NOT NULL, ";	// Number of times the userkey cookie has been used when accessing the site
		$query .= "lastcommentid int(11) NULL, "; // Last comment created by user
		$query .= "PRIMARY KEY (userid) ";
		$query .= ") ";

		sql_query($query);

		if($this->_checkIndexIfExists($this->getTableUser(), 'userkey_idx') == 0)
		{
			$query  = "CREATE UNIQUE INDEX userkey_idx ON ".$this->getTableUser()." (userkey)";
			sql_query($query);
		}
	}

	function _createTableFilter()
	{
		$query  = "CREATE TABLE IF NOT EXISTS ".$this->getTableFilter();
		$query .= "( ";
		$query .= "filterid int(11) NOT NULL auto_increment, ";
		$query .= "type char(1) NOT NULL, "; // Filter type: 'I' - IP, 'E' - EMail, 'M' - Memberid, 'W' - Website
		$query .= "filter varchar(100) NOT NULL, ";
		$query .= "modcategory char(1) NOT NULL, "; // 'H' - Ham, 'S' - Spam, 'M' - Manual Moderation
		$query .= "status char(1) NOT NULL, "; // 'L' - Locked (SpamMark don't update modcategory)
		$query .= "created datetime NOT NULL, "; 
		$query .= "lastused datetime NOT NULL, "; // Last time the filter was used
		$query .= "usecount int(11) NOT NULL, ";	// Number of times the filter has been used
		$query .= "PRIMARY KEY (filterid) ";
		$query .= ") ";

		sql_query($query);

		if($this->_checkIndexIfExists($this->getTableFilter(), 'filter_idx') == 0)
		{
			$query  = "CREATE INDEX filter_idx ON ".$this->getTableFilter()." (filter, type)";
			sql_query($query);
		}
	}

	function _checkIndexIfExists($table, $index)
	{
		// Retuns: 0: Not found , 1: Found, false: error
		$found = false;
		
		$res = sql_query("SELECT Count(*) AS cnt FROM INFORMATION_SCHEMA.STATISTICS "
						."WHERE table_name = '".$table."' AND index_name = '".$index."' ");

		if($res)
		{
			while ($o = sql_fetch_object($res)) 
			{
				$found = $o->cnt;
			}
		}
		
		return $found;
	}

	function _needReplacementVarsSourceVersion()
	{
		return '1.1.0';
	}
	
	function _checkReplacementVarsSourceVersion()
	{
		$replacementVarsVersion = $this->_needReplacementVarsSourceVersion();
		$aVersion = explode('.', $replacementVarsVersion);
		$needmajor = $aVersion['0']; $needminor = $aVersion['1']; $needpatch = $aVersion['2'];
		
		$replacementVarsVersion = $this->_getReplacementVarsPlugin()->getVersion();
		$aVersion = explode('.', $replacementVarsVersion);
		$major = $aVersion['0']; $minor = $aVersion['1']; $patch = $aVersion['2'];
		
		if($major < $needmajor || (($major == $needmajor) && ($minor < $needminor)) || (($major == $needmajor) && ($minor == $needminor) && ($patch < $needpatch)))
		{
			return false;
		}

		return true;
	}
}
?>
