<?php
/**	Class Name: GroupManagementPage
	FOGPage lives in: {fogwebdir}/lib/fog
	Lives in: {fogwebdir}/lib/pages
    Description: This is an extention of the FOGPage Class
    This class controls the group management page for FOG.
	It, now, allows group creation within as opposed to the
	old method it used.

	Manages group settings such as:
	Image Association, Active Directory, Snapin Add and removal,
	Printer association, and Service configurations.

	Useful for:
	Making setting changes quickly on multiple systems at a time.
**/
class GroupManagementPage extends FOGPage
{
	// Base variables
	var $name = 'Group Management';
	var $node = 'group';
	var $id = 'id';
	// Menu Items
	var $menu = array(
	);
	var $subMenu = array(
	);
	// __construct
	/** __construct($name = '')
		Builds the default header and template information
		for the Group page.
		This builds the default display for index and search.
	*/
	public function __construct($name = '')
	{
		// Call parent constructor
		parent::__construct($name);
		// Header row
		$this->headerData = array(
			'<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" checked/>',
			_('Name'),
			_('Members'),
			_('Tasking'),
			_('Edit/Remove'),
		);
		// Row templates
		$this->templates = array(
			'<input type="checkbox" name="group[]" value="${id}" class="toggle-action" checked/>',
			sprintf('<a href="?node=group&sub=edit&%s=${id}" title="Edit">${name}</a>', $this->id),
			'${count}',
			sprintf('<a href="?node=group&sub=deploy&type=1&%s=${id}"><i class="icon fa fa-arrow-down" title="Download"></i></a> <a href="?node=group&sub=deploy&type=8&%s=${id}"><i class="icon fa fa-share-alt" title="Multi-cast"></i></a> <a href="?node=group&sub=edit&%s=${id}#group-tasks"><i class="icon fa fa-arrows-alt" title="Deploy"></i></a>', $this->id, $this->id, $this->id, $this->id, $this->id, $this->id),
			sprintf('<a href="?node=group&sub=edit&%s=${id}"><i class="icon fa fa-pencil" title="Edit"></i></a> <a href="?node=group&sub=delete&%s=${id}"><i class="icon fa fa-minus-circle" title="Delete"></i></a>', $this->id, $this->id, $this->id, $this->id, $this->id, $this->id),
		);
		// Row attributes
		$this->attributes = array(
			array('width' => 16, 'class' => 'c'),
			array(),
			array('width' => 30, 'class' => 'c'),
			array('width' => 90, 'class' => 'c'),
			array('width' => 50, 'class' => 'c'),
		);
	}
	// Pages
	/** index()
		This is the first page displayed.  However, if search is used
		as the default view, this isn't displayed.  But it still serves
		as a means to display data, if there was a problem with the search
		function.
	*/
	public function index()
	{
		// Set title
		$this->title = _('All Groups');
		// Find data
		if ($_SESSION['DataReturn'] > 0 && $_SESSION['GroupCount'] > $_SESSION['DataReturn'] && $_REQUEST['sub'] != 'list')
			$this->FOGCore->redirect(sprintf('%s?node=%s&sub=search', $_SERVER['PHP_SELF'], $this->node));
		// Row data
		foreach ($this->getClass('GroupManager')->find() AS $Group)
		{
			$this->data[] = array(
				'id' => $Group->get('id'),
				'name' => $Group->get('name'),
				'description' => $Group->get('description'),
				'count' => $Group->getHostCount(),
			);
		}
		// Hook
		$this->HookManager->processEvent('GROUP_DATA', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
	}
	/** search_post()
		This function is how the data gets processed and displayed based on what was
		searched for.
	*/
	public function search_post()
	{
		// Variables
		$Groups = new GroupManager();
		// Find data -> Push data
		foreach($Groups->search() AS $Group)
		{
			$this->data[] = array(
				'id'		=> $Group->get('id'),
				'name'		=> $Group->get('name'),
				'description'	=> $Group->get('description'),
				'count' => $Group->getHostCount(),
			);
		}
		// Hook
		$this->HookManager->processEvent('GROUP_DATA', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
	}
	/** add()
		This function is what creates the new group.
		You can do this from two places.  You can do it from the
		Host List, but now you can also do it from the Group page
		as well.  In years past, you could only create a group using
		the host list page.
	*/
	public function add()
	{
		// Set title
		$this->title = _('New Group');
		// Headerdata
		unset($this->headerData);
		// Attributes
		$this->attributes = array(
			array(),
			array(),
		);
		// Templates
		$this->templates = array(
			'${field}',
			'${formField}',
		);
		$fields = array(
			_('Group Name') => '<input type="text" name="name" />',
			_('Group Description') => '<textarea name="description" rows="8" cols="40"></textarea>',
			_('Group Kernel') => '<input type="text" name="kern" />',
			_('Group Kernel Arguments') => '<input type="text" name="args" />',
			_('Group Primary Disk') => '<input type="text" name="dev" />',
			'' => '<input type="submit" value="'._('Add').'" />',
		);
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'">';
		foreach ((array)$fields AS $field => $formField)
		{
			$this->data[] = array(
				'field' => $field,
				'formField' => $formField,
			);
		}
		// Hook
		$this->HookManager->processEvent('GROUP_ADD', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		print '</form>';
	}
	/** add_post()
		This is the function that actually creates the group.
	*/
	public function add_post()
	{
		// Hook
		$this->HookManager->processEvent('GROUP_ADD_POST');
		// POST
		try
		{
			// Error checking
			if (empty($_REQUEST['name']))
				throw new Exception('Group Name is required');
			if ($this->getClass('GroupManager')->exists($_REQUEST['name']))
				throw new Exception('Group Name already exists');
			// Define new Image object with data provided
			$Group = new Group(array(
				'name'		=> $_REQUEST['name'],
				'description'	=> $_REQUEST['description'],
				'kernel'	=> $_REQUEST['kern'],
				'kernelArgs'	=> $_REQUEST['args'],
				'kernelDevice'	=> $_REQUEST['dev']
			));
			// Save to database
			if ($Group->save())
			{
				// Hook
				$this->HookManager->processEvent('GROUP_ADD_SUCCESS', array('Group' => &$Group));
				// Log History event
				$this->FOGCore->logHistory(sprintf('%s: ID: %s, Name: %s', _('Group added'), $Group->get('id'), $Group->get('name')));
				// Set session message
				$this->FOGCore->setMessage(_('Group added'));
				// Redirect to new entry
				$this->FOGCore->redirect(sprintf('?node=%s&sub=edit&%s=%s', $this->request['node'], $this->id, $Group->get('id')));
			}
			else
				throw new Exception('Database update failed');
		}
		catch (Exception $e)
		{
			// Hook
			$this->HookManager->processEvent('GROUP_ADD_FAIL', array('Group' => &$Group));
			// Log History event
			$this->FOGCore->logHistory(sprintf('%s add failed: Name: %s, Error: %s', _('Group'), $_REQUEST['name'], $e->getMessage()));
			// Set session message
			$this->FOGCore->setMessage($e->getMessage());
			// Redirect to new entry
			$this->FOGCore->redirect($this->formAction);
		}
	}
	/** edit()
		This is how you edit a group.  You can also
		add hosts from this page now.  You used to only
		be able to add hosts to the groups from the host list page.
		This should make some things easier.  You can also use it
		to setup tasks for the group, snapins, printers, active directory,
		images, etc...
	*/
	public function edit()
	{
		// Find
		$Group = new Group($_REQUEST[$this->id]);
		// If all hosts have the same image setup up the selection.
		foreach ((array)$Group->get('hosts') AS $Host)
		{
			if ($Host && $Host->isValid())
			{
				$imageID[] = $Host && $Host->isValid() ? $Host->get('imageID') : '';
				$groupKey[] = $Host && $Host->isValid() ? base64_decode($Host->get('productKey')) : '';
			}
		}
		$imageIDMult = (is_array($imageID) ? array_unique($imageID) : $imageID);
		$groupKeyMult = (is_array($groupKey) ? array_unique($groupKey) : $groupKey);
		$groupKeyMult = array_filter((array)$groupKeyMult);
		if (count($imageIDMult) == 1)
			$imageMatchID = $Host && $Host->isValid() ? $Host->get('imageID') : '';
		// Title - set title for page title in window
		$this->title = sprintf('%s: %s', _('Edit'), $Group->get('name'));
		// Headerdata
		unset ($this->headerData);
		// Attributes
		$this->attributes = array(
			array(),
			array(),
		);
		// Templates
		$this->templates = array(
			'${field}',
			'${input}',
		);
		$fields = array(
			_('Group Name') => '<input type="text" name="name" value="${group_name}" />',
			_('Group Description') => '<textarea name="description" rows="8" cols="40">${group_desc}</textarea>',
			_('Group Product Key') => '<input id="productKey" type="text" name="key" value="${group_key}" />',
			_('Group Kernel') => '<input type="text" name="kern" value="${group_kern}" />',
			_('Group Kernel Arguments') => '<input type="text" name="args" value="${group_args}" />',
			_('Group Primary Disk') => '<input type="text" name="dev" value="${group_devs}" />',
			'<input type="hidden" name="updategroup" value="1" />' => '<input type="submit" value="'._('Update').'" />',
		);
		//$this->HookManager->processEvent('GROUP_FIELDS',array('fields' => &$fields,'Group' => &$Group));
		print "\n\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-general">';
		print "\n\t\t\t".'<input type="hidden" name="'.$this->id.'" value="'.$_REQUEST['id'].'" />';
		print "\n\t\t\t".'<div id="tab-container">';
		print "\n\t\t\t<!-- General -->";
		print "\n\t\t\t".'<div id="group-general">';
		print "\n\t\t\t".'<h2>'._('Modify Group').': '.$Group->get('name').'</h2>';
		foreach ((array)$fields AS $field => $input)
		{
			$this->data[] = array(
				'field' => $field,
				'input' => $input,
				'group_name' => $Group->get('name'),
				'group_desc' => $Group->get('description'),
				'group_kern' => $Group->get('kernel'),
				'group_args' => $Group->get('kernelArgs'),
				'group_devs' => $Group->get('kernelDev'),
				'group_key' => count($groupKeyMult) == 1 ? $groupKeyMult[0] : '',
			);
		}
		// Hook
		$this->HookManager->processEvent('GROUP_DATA_GEN', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		unset ($this->data);
		print "</div>";
		print "\n\t\t\t</form>";
		$this->basictasksOptions();
		print "\n\t\t\t<!-- Membership -->";
		// Hopeful implementation of all groups to add to group system in similar means to how host page does from list/search functions.
		print "\n\t\t\t".'<div id="group-membership">';
		// Create the Header data:
		$this->headerData = array(
			'',
			'<input type="checkbox" name="toggle-checkboxgroup1" class="toggle-checkbox1" />',
			_('Host Name'),
			_('Image'),
		);
		// Create the template data:
		$this->templates = array(
			'<span class="icon fa fa-question fa-1x hand" title="${host_desc}"></span>',
			'<input type="checkbox" name="host[]" value="${host_id}" class="toggle-host${check_num}" />',
			'<a href="?node=host&sub=edit&id=${host_id}" title="Edit: ${host_name} Was last deployed: ${deployed}">${host_name}</a><br /><small>${host_mac}</small>',
			'${image_name}',
		);
		// Create the attributes to build the table info:
		$this->attributes = array(
			array('width' => 22, 'id' => 'host-${host_name}'),
			array('class' => 'c', 'width' => 16),
			array(),
			array(),
		);
		// All hosts not in this group.
		foreach((array)$Group->get('hostsnotinme') AS $Host)
		{
			if ($Host && $Host->isValid() && !$Host->get('pending'))
			{
				$this->data[] = array(
					'host_id' => $Host->get('id'),
					'deployed' => $this->validDate($Host->get('deployed')) ? $this->FOGCore->formatTime($Host->get('deployed')) : 'No Data',
					'host_name' => $Host->get('name'),
					'host_mac' => $Host->get('mac')->__toString(),
					'host_desc' => $Host->get('description'),
					'image_name' => $Host->getImage()->get('name'),
					'check_num' => '1'
				);
			}
		}
		$GroupDataExists = false;
		if (count($this->data) > 0)
		{
			$GroupDataExists = true;
			$this->HookManager->processEvent('GROUP_HOST_NOT_IN_ME',array('headerData' => &$this->headerData,'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
			$groupAdd[] = "\n\t\t\t<center>".'<label for="hostMeShow">'._('Check here to see hosts not in this group').'&nbsp;&nbsp;<input type="checkbox" name="hostMeShow" id="hostMeShow" /></label>';
			$groupAdd[] = "\n\t\t\t".'<div id="hostNotInMe">';
			$groupAdd[] = "\n\t\t\t".'<h2>'._('Modify Membership for').' '.$Group->get('name').'</h2>';
			$groupAdd[] = "\n\t\t\t".'<p>'._('Add hosts to group').' '.$Group->get('name').'</p>';
			$groupAdd[] = implode("\n",$this->process());
			$groupAdd[] = "</div></center>";
		}
		// Reset the data for the next value
		unset($this->data);
		// Create the Header data
		$this->headerData = array(
			'',
			'<input type="checkbox" name="toggle-checkboxgroup2" class="toggle-checkbox2" />',
			_('Host Name'),
			_('Image'),
		);
		if ($GroupDataExists)
		{
			$groupAdd[] = '<br/><center><input type="submit" value="'._('Add Host(s) to Group').'" name="addHosts"/>';
			$groupAdd[] = "\n\t\t\t</center><br/>";
		}
		if ($groupAdd)
		{
			print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-membership">';
			print implode($groupAdd);
			print '</form>';
		}
		unset($this->data);
		$this->headerData = array(
			'<input type="checkbox" name="toggle-checkbox" class="toggle-checkboxAction" checked/>',
            _('Hostname'),
            _('Deployed'),
            _('Image'),
		);
		$this->attributes = array(
            array('class' => 'c','width' => 16),
            array(),
            array(),
            array(),
		);
		$this->templates = array(
			'<input type="checkbox" name="member[]" value="${host_id}" class="toggle-action" checked/>',
			'<a href="?node=host&sub=edit&id=${host_id}" title="Edit: ${host_name} Was last deployed: ${deployed}">${host_name}</a><br /><small>${host_mac}</small>',
			'<small>${deployed}</small>',
			'<small>${image_name}</small>',
		);
		$imageSelector = $this->getClass('ImageManager')->buildSelectBox('','','','',true);
		foreach ((array)$Group->get('hosts') AS $Host)
		{
			if ($Host && $Host->isValid())
			{
				$this->data[] = array(
                    'host_id'   => $Host->get('id'),
                    'deployed' => $this->validDate($Host->get('deployed')) ? $this->FOGCore->formatTime($Host->get('deployed')) : 'No Data',
                    'host_name' => $Host->get('name'),
                    'host_mac'  => $Host->get('mac'),
					'image_name' => $imageSelector,
					'selected_item'.$Host->get('imageID') => 'selected',
					'selector_name' => $Host->get('name').'_'.$Host->get('id'),
				);
			}
		}
		// Hook
		$this->HookManager->processEvent('GROUP_MEMBERSHIP', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-membership">';
		// Output
		$this->render();
		if (count($this->data) > 0)
		{
			print "\n\t\t\t".'<center><input type="submit" value="'._('Update Hosts').'" name="updatehosts"/>&nbsp;&nbsp;';
			print "\n\t\t\t".'<input type="submit" value="'._('Delete Selected Hosts From Group').'" name="remhosts"/></center>';
		}
		print "\n\t\t\t</form>";
		print "\n\t\t\t</div>";
		unset($this->data);
		print "\n\t\t\t<!-- Image Association -->";
		print "\n\t\t\t".'<div id="group-image">';
		print "\n\t\t\t<h2>"._('Image Association for').': '.$Group->get('name').'</h2>';
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-image">';
		unset($this->headerData);
		$this->attributes = array(
			array(),
			array(),
		);
		$this->templates = array(
			'${field}',
			'${input}',
		);
		$this->data[] = array(
			'field' => $imageSelector,
			'selected_item'.$imageMatchID => 'selected',
			'selector_name' => 'image',
			'input' => '<input type="submit" value="'._('Update Images').'" />',
		);
		// Hook
		$this->HookManager->processEvent('GROUP_IMAGE', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		unset($this->data);
		print "</form>";
		print "\n\t\t\t</div>";
		print "\n\t\t\t<!-- Add Snap-ins -->";
		print "\n\t\t\t".'<div id="group-snap-add">';
		print "\n\t\t\t<h2>"._('Add Snapin to all hosts in').': '.$Group->get('name').'</h2>';
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-snap-add">';
		$this->headerData = array(
			'<input type="checkbox" name="toggle-checkboxsnapin" class="toggle-checkboxsnapin" />',
			_('Snapin Name'),
			_('Created'),
		);
		$this->templates = array(
			'<input type="checkbox" name="snapin[]" value="${snapin_id}" class="toggle-snapin" />',
			'<a href="?node=snapin&sub=edit&id=${snapin_id}" title="'._('Edit').'">${snapin_name}</a>',
			'${snapin_created}',
		);
		$this->attributes = array(
			array('width' => 16, 'class' => 'c'),
			array('width' => 90, 'class' => 'l'),
			array('width' => 20, 'class' => 'r'),
		);
		// Get all snapins.
		foreach($this->getClass('SnapinManager')->find() AS $Snapin)
		{
			if ($Snapin && $Snapin->isValid())
			{
				$this->data[] = array(
					'snapin_id' => $Snapin->get('id'),
					'snapin_name' => $Snapin->get('name'),
					'snapin_created' => $this->formatTime($Snapin->get('createdTime')),
				);
			}
		}
		// Hook
		$this->HookManager->processEvent('GROUP_SNAP_ADD', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		print "\n\t\t\t".'<center><input type="submit" value="'._('Add Snapin(s)').'" /></center>';
		unset($this->data);
		print '</form>';
		print "\n\t\t\t</div>";
		print "\n\t\t\t<!-- Remove Snap-ins -->";
		print "\n\t\t\t".'<div id="group-snap-del">';
		print "\n\t\t\t<h2>"._('Remove Snapin to all hosts in').': '.$Group->get('name').'</h2>';
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-snap-del">';
		$this->headerData = array(
			'<input type="checkbox" name="toggle-checkboxsnapinrm" class="toggle-checkboxsnapinrm" />',
			_('Snapin Name'),
			_('Created'),
		);
		$this->templates = array(
			'<input type="checkbox" name="snapin[]" value="${snapin_id}" class="toggle-snapinrm" />',
			'<a href="?node=snapin&sub=edit&id=${snapin_id}" title="'._('Edit').'">${snapin_name}</a>',
			'${snapin_created}',
		);
		$this->attributes = array(
			array('width' => 16, 'class' => 'c'),
			array('width' => 90, 'class' => 'l'),
			array('width' => 20, 'class' => 'r'),
		);
		// Get all snapins.
		foreach($this->getClass('SnapinManager')->find() AS $Snapin)
		{
			if ($Snapin && $Snapin->isValid())
			{
				$this->data[] = array(
					'snapin_id' => $Snapin->get('id'),
					'snapin_name' => $Snapin->get('name'),
					'snapin_created' => $Snapin->get('createdTime'),
				);
			}
		}
		// Hook
		$this->HookManager->processEvent('GROUP_SNAP_DEL', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		print "\n\t\t\t".'<center><input type="submit" value="'._('Remove Snapin(s)').'" /></center>';
		unset($this->headerData,$this->data);
		print '</form>';
		print "\n\t\t\t</div>";
		print "\n\t\t\t<!-- Service Settings -->";
		$this->attributes = array(
			array('width' => 270),
			array('class' => 'c'),
			array('class' => 'r'),
		);
		$this->templates = array(
			'${mod_name}',
			'${input}',
			'${span}',
		);
		$this->data[] = array(
			'mod_name' => 'Select/Deselect All',
			'input' => '<input type="checkbox" class="checkboxes" id="checkAll" name="checkAll" value="checkAll" />',
			'span' => ''
		);
		print "\n\t\t\t".'<div id="group-service">';
		print "\n\t\t\t".'<h2>'._('Service Configuration').'</h2>';
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-service">';
		print "\n\t\t\t<fieldset>";
		print "\n\t\t\t<legend>"._('General')."</legend>";
		foreach((array)$Group->get('hosts') AS $Host)
		{
			if ($Host && $Host->isValid() && !$ModOns[$Host->get('id')])
				$ModOns[$Host->get('id')] = $this->getClass('ModuleAssociationManager')->find(array('hostID' => $Host->get('id')),'','','','','','','moduleID');
		}
		$moduleName = $this->getGlobalModuleStatus();
        foreach ((array)$this->getClass('ModuleManager')->find() AS $Module)
        {
			if ($Module && $Module->isValid())
			{
				$i = 0;
				foreach($ModOns AS $Host => $ModOn)
				{
					if (in_array($Module->get('id'),$ModOn))
						$i++;
				}
				$this->data[] = array(
					'input' => '<input type="checkbox" '.($moduleName[$Module->get('shortname')] || ($moduleName[$Module->get('shortName')] && $Module->get('isDefault')) ? 'class="checkboxes"' : '').' name="modules[]" value="${mod_id}" ${checked} '.(!$moduleName[$Module->get('shortName')] ? 'disabled="disabled"' : '').' />',
					'span' => '<span class="icon fa fa-question fa-1x hand" title="${mod_desc}"></span>',
					'checked' => ($i == $Group->getHostCount() ? 'checked' : ''),
					'mod_name' => $Module->get('name'),
					'mod_shname' => $Module->get('shortName'),
					'mod_id' => $Module->get('id'),
					'mod_desc' => str_replace('"','\"',$Module->get('description')),
				);
			}
		}
		unset($ModOns);
		$this->data[] = array(
			'mod_name' => '',
			'input' => '',
			'span' => '<input type="submit" name="updatestatus" value="'._('Update').'" />',
		);
		// Hook
		$this->HookManager->processEvent('GROUP_MODULES', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		unset($this->data);
		print "</fieldset>";
		print "\n\t\t\t</form>";
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-service">';
		print "\n\t\t\t<fieldset>";
		print "\n\t\t\t<legend>"._('Group Screen Resolution').'</legend>';
		$this->attributes = array(
				array('class' => 'l','style' => 'padding-right: 25px'),
				array('class' => 'c'),
				array('class' => 'r'),
		);
		$this->templates = array(
			'${field}',
			'${input}',
			'${span}',
		);
		$Services = $this->getClass('ServiceManager')->find(array('name' => array('FOG_SERVICE_DISPLAYMANAGER_X','FOG_SERVICE_DISPLAYMANAGER_Y','FOG_SERVICE_DISPLAYMANAGER_R')),'OR','id');
		foreach((array)$Services AS $Service)
		{
			$this->data[] = array(
				'input' => '<input type="text" name="${type}" value="${disp}" />',
				'span' => '<span class="icon fa fa-question fa-1x hand" title="${desc}"></span>',
				'field' => ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_X' ? _('Screen Width (in pixels)') : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_Y' ? _('Screen Height (in pixels)') : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_R' ? _('Screen Refresh Rate (in Hz)') : null))),
				'type' => ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_X' ? 'x' : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_Y' ? 'y' : ($Service->get('name') == 'FOG_SERVICE_DISPLAYMANAGER_R' ? 'r' : null))),
				'disp' => $Service->get('value'),
				'desc' => $Service->get('description'),
			);
		}
		$this->data[] = array(
			'field' => '',
			'input' => '',
			'span' => '<input type="submit" name="updatedisplay" value="'._('Update').'" />',
		);
		// Hook
		$this->HookManager->processEvent('GROUP_DISPLAY', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		unset($this->data);
		print '</fieldset>';
		print "\n\t\t\t</form>";
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-service">';
		print "\n\t\t\t<fieldset>";
		print "\n\t\t\t<legend>"._('Auto Log Out Settings').'</legend>';
		$this->attributes = array(
			array('width' => 270),
			array('class' => 'c'),
			array('class' => 'r'),
		);
		$this->templates = array(
			'${field}',
			'${input}',
			'${desc}',
		);
		$Service = current($this->getClass('ServiceManager')->find(array('name' => 'FOG_SERVICE_AUTOLOGOFF_MIN')));
		$this->data[] = array(
			'field' => _('Auto Log Out Time (in minutes)'),
			'input' => '<input type="text" name="tme" value="${value}" />',
			'desc' => '<span class="icon fa fa-question fa-1x hand" title="${serv_desc}"></span>',
			'value' => $Service->get('value'),
			'serv_desc' => $Service->get('description'),
		);
		$this->data[] = array(
			'field' => '',
			'input' => '',
			'desc' => '<input type="submit" name="updatealo" value="'._('Update').'" />',
		);
		// Hook
		$this->HookManager->processEvent('GROUP_ALO', array('headerData' => &$this->headerData, 'data' => &$this->data, 'templates' => &$this->templates, 'attributes' => &$this->attributes));
		// Output
		$this->render();
		unset($this->data);
		print '</fieldset>';
		print "\n\t\t\t</form>";
		print "\n\t\t\t</div>";
		$this->adFieldsToDisplay();
		print "\n\t\t\t<!-- Printers -->";
		print "\n\t\t\t".'<div id="group-printers">';
		print "\n\t\t\t".'<form method="post" action="'.$this->formAction.'&tab=group-printers">';
		print "\n\t\t\t<h2>"._('Select Management Level for all Hosts in this group').'</h2>';
		print "\n\t\t\t".'<p class="l">';
		print "\n\t\t\t\t".'<input type="radio" name="level" value="0" />'._('No Printer Management').'<br/>';
		print "\n\t\t\t\t".'<input type="radio" name="level" value="1" />'._('Add Only').'<br/>';
		print "\n\t\t\t\t".'<input type="radio" name="level" value="2" />'._('Add and Remove').'<br/>';
		print "\n\t\t\t</p>";
		print "\n\t\t\t".'<div class="hostgroup">';
		// Create Header for printers
		$this->headerData = array(
			//prntadd
			'<input type="checkbox" name="toggle-checkboxprint" class="toggle-checkboxprint" />',
			_('Default'),
			_('Printer Name'),
			_('Configuration'),
		);
		// Create Template for Printers:
		$this->templates = array(
			'<input type="checkbox" name="prntadd[]" value="${printer_id}" class="toggle-print" />',
			'<input class="default" type="radio" name="default" id="printer${printer_id}" value="${printer_id}" /><label for="printer${printer_id}" class="icon icon-hand" title="'._('Default Printer Selector').'">&nbsp;</label><input type="hidden" name="printerid[]" />',
			'<a href="?node=printer&sub=edit&id=${printer_id}">${printer_name}</a>',
			'${printer_type}',
		);
		$this->attributes = array(
			array('width' => 16, 'class' => 'c'),
			array('width' => 20),
			array('width' => 50, 'class' => 'l'),
			array('width' => 50, 'class' => 'r'),
		);
		foreach($this->getClass('PrinterManager')->find() AS $Printer)
		{
			if ($Printer && $Printer->isValid())
			{
				$this->data[] = array(
					'printer_id' => $Printer->get('id'),
					'printer_name' => addslashes($Printer->get('name')),
					'printer_type' => $Printer->get('config'),
				);
			}
		}
		if (count($this->data) > 0)
		{
			print "\n\t\t\t<h2>"._('Add new printer(s) to all hosts in this group.').'</h2>';
			$this->HookManager->processEvent('GROUP_ADD_PRINTER',array('data' => &$this->data,'templates' => &$this->templates,'headerData' => &$this->headerData,'attributes' => &$this->attributes));
			$this->render();
			unset($this->data);
		}
		else
			print "\n\t\t\t<h2>"._('There are no printers to assign.').'</h2>';
		print "\n\t\t\t</div>";
		print "\n\t\t\t".'<div class="hostgroup">';
		$this->headerData = array(
			'<input type="checkbox" name="toggle-checkboxprint" class="toggle-checkboxprintrm" />',
			_('Printer Name'),
			_('Configuration'),
		);
		// Create Template for Printers:
		$this->templates = array(
			'<input type="checkbox" name="prntdel[]" value="${printer_id}" class="toggle-printrm" />',
			'${printer_name}',
			'${printer_type}',
		);
		$this->attributes = array(
			array('width' => 16, 'class' => 'c'),
			array('width' => 50, 'class' => 'l'),
			array('width' => 50, 'class' => 'r'),
		);
		foreach($this->getClass('PrinterManager')->find() AS $Printer)
		{
			if ($Printer && $Printer->isValid())
			{
				$this->data[] = array(
					'printer_id' => $Printer->get('id'),
					'printer_name' => addslashes($Printer->get('name')),
					'printer_type' => $Printer->get('config'),
				);
			}
		}
		if (count($this->data) > 0)
		{

			print "\n\t\t\t<h2>"._('Remove printer from all hosts in this group.').'</h2>';
			$this->HookManager->processEvent('GROUP_REM_PRINTER',array('data' => &$this->data,'templates' => &$this->templates, 'headerData' => &$this->headerData, 'attributes' => &$this->attributes));
			$this->render();
			unset($this->data);
		}
		else
			print "\n\t\t\t<h2>"._('There are no printers to assign.').'</h2>';
		print "\n\t\t\t</div>";
		print "\n\t\t\t".'<input type="hidden" name="update" value="1" /><input type="submit" value="'._('Update').'" />';
		print "\n\t\t\t</form>";
		print "\n\t\t\t</div>";
		print "\n\t\t\t</div>";
	}
	/** edit_post()
		This updates the information from the edit function.
	*/
	public function edit_post()
	{
		// Find
		$Group = new Group($_REQUEST['id']);
		// Hook
		$this->HookManager->processEvent('GROUP_EDIT_POST', array('Group' => &$Group));
		// Group Edit 
		try
		{
			switch($_REQUEST['tab'])
			{
				// Group Main Edit
				case 'group-general';
					// Error checking
					if (empty($_REQUEST['name']))
						throw new Exception('Group Name is required');
					else
					{
						// Define new Image object with data provided
						$Group	->set('name',		$_REQUEST['name'])
								->set('description',	$_REQUEST['description'])
								->set('kernel',		$_REQUEST['kern'])
								->set('kernelArgs',	$_REQUEST['args'])
								->set('kernelDevice',	$_REQUEST['dev']);
						foreach((array)$Group->get('hosts') AS $Host)
						{
							if ($Host && $Host->isValid())
							{
								$Host->set('kernel',		$_REQUEST['kern'])
									 ->set('kernelArgs',	$_REQUEST['args'])
									 ->set('kernelDevice',	$_REQUEST['dev'])
									 ->set('productKey', $_REQUEST['key'])
									 ->save();
							}
						}
					}
				break;
				// Group membership
				case 'group-membership';
					if (isset($_REQUEST['addHosts']))
						$Group->addHost($_REQUEST['host']);
					if (isset($_REQUEST['updatehosts']))
					{
						foreach((array)$Group->get('hosts') AS $Host)
						{
							if ($Host && $Host->isValid())
								$Host->set('imageID',$_REQUEST[$Host->get('name').'_'.$Host->get('id')])->save();
						}
					}
					if(isset($_REQUEST['remhosts']))
						$Group->removeHost($_REQUEST['member']);
				break;
				// Image Association
				case 'group-image';
					$Group->addImage($_REQUEST['image']);
				break;
				// Snapin Add
				case 'group-snap-add';
					$Group->addSnapin($_REQUEST['snapin']);
				break;
				// Snapin Del
				case 'group-snap-del';
					$Group->removeSnapin($_REQUEST['snapin']);
				break;
				// Active Directory
				case 'group-active-directory';
					$useAD = ($_REQUEST['domain'] == 'on');
					$domain = $_REQUEST['domainname'];
					$ou = $_REQUEST['ou'];
					$user = $_REQUEST['domainuser'];
					$pass = $_REQUEST['domainpassword'];
					$Group->setAD($useAD,$domain,$ou,$user,$pass);
				break;
				// Printer Add/Rem
				case 'group-printers';
					$Group->addPrinter($_REQUEST['prntadd'],$_REQUEST['prntdel'],$_REQUEST['level']);
					$Group->updateDefault($_REQUEST['printerid'],$_REQUEST['default']);
				break;
				// Update Services
				case 'group-service';
                    // The values below set the display Width, Height, and Refresh.  If they're not set by you, they'll
                    // be set to the default values within the system.
                    $x =(is_numeric($_REQUEST['x']) ? $_REQUEST['x'] : $this->FOGCore->getSetting('FOG_SERVICE_DISPLAYMANAGER_X'));
                    $y =(is_numeric($_REQUEST['y']) ? $_REQUEST['y'] : $this->FOGCore->getSetting('FOG_SERVICE_DISPLAYMANAGER_Y'));
                    $r =(is_numeric($_REQUEST['r']) ? $_REQUEST['r'] : $this->FOGCore->getSetting('FOG_SERVICE_DISPLAYMANAGER_R'));
                    $tme = (is_numeric($_REQUEST['tme']) ? $_REQUEST['tme'] : $this->FOGCore->getSetting('FOG_SERVICE_AUTOLOGOFF_MIN'));
					$modOn = $_REQUEST['modules'];
					$modOff = $this->getClass('ModuleManager')->find(array('id' => $modOn),'','','','','',true,'id');
					foreach((array)$Group->get('hosts') AS $Host)
					{
						if ($Host && $Host->isValid())
						{
							if (isset($_REQUEST['updatestatus']))
							{
								$Host->removeModule($modOff);
								$Host->addModule($modOn);
							}
							if (isset($_REQUEST['updatedisplay']))
								$Host->setDisp($x,$y,$r);
							if (isset($_REQUEST['updatealo']))
								$Host->setAlo($tme);
							$Host->save();
						}
					}
                break;
			}
            // Save to database
			if ($Group->save())
			{
				// Hook
				$this->HookManager->processEvent('GROUP_EDIT_SUCCESS', array('Group' => &$Group));
				// Log History event
				$this->FOGCore->logHistory(sprintf('Group updated: ID: %s, Name: %s', $Group->get('id'), $Group->get('name')));
				// Set session message
				$this->FOGCore->setMessage('Group information updated!');
				// Redirect to new entry
				$this->FOGCore->redirect(sprintf('?node=%s&sub=edit&%s=%s#%s', $this->request['node'], $this->id, $Group->get('id'), $this->REQUEST['tab']));
			}
			else
				throw new Exception('Database update failed');
		}
		catch (Exception $e)
		{
			// Hook
			$this->HookManager->processEvent('GROUP_EDIT_FAIL', array('Group' => &$Group));
			// Log History event
			$this->FOGCore->logHistory(sprintf('%s update failed: Name: %s, Error: %s', _('Group'), $_REQUEST['name'], $e->getMessage()));
			// Set session message
			$this->FOGCore->setMessage($e->getMessage());
			// Redirect
			$this->FOGCore->redirect(sprintf('?node=%s&sub=edit&%s=%s#%s', $this->request['node'], $this->id, $Group->get('id'), $this->request['tab']));
		}
	}
	public function delete_hosts()
	{
		$Group = new Group($_REQUEST['id']);
		$this->title = _('Delete Hosts');
		unset($this->data);
		// Header Data
		$this->headerData = array(
			_('Host Name'),
			_('Last Deployed'),
		);
		// Attributes
		$this->attributes = array(
			array(),
			array(),
		);
		// Templates
		$this->templates = array(
			'${host_name}<br/><small>${host_mac}</small>',
			'<small>${host_deployed}</small>',
		);
		foreach((array)$Group->get('hosts') AS $Host)
		{
			if ($Host && $Host->isValid())
			{
				$this->data[] = array(
					'host_name' => $Host->get('name'),
					'host_mac' => $Host->get('mac'),
					'host_deployed' => $Host->get('deployed'),
				);
			}
		}
		printf('%s<p>%s</p>',"\n\t\t\t",_('Confirm you really want to delete the following hosts'));
		printf('%s<form method="post" action="?node=group&sub=delete&id=%s" class="c">',"\n\t\t\t",$Group->get('id'));
		// Hook
		$this->HookManager->processEvent('GROUP_DELETE_HOST_FORM',array('headerData' => &$this->headerData,'data' => &$this->data,'templates' => &$this->templates,'attributes' => &$this->attributes));
		// Output
		$this->render();
		printf('<input type="hidden" name="delHostConfirm" value="1" /><input type="submit" value="%s" />',_('Delete listed hosts'));
		printf('</form>');
	}
	public function deletemulti()
	{
		$this->title = _('Groups to remove');
		unset($this->headerData);
		print "\n\t\t\t".'<div class="confirm-message">';
		print "\n\t\t\t<p>"._('Groups to be removed').":</p>";
		$this->attributes = array(
			array(),
		);
		$this->templates = array(
			'<a href="?node=group&sub=edit&id=${group_id}">${group_name}</a>',
		);
		foreach ((array)explode(',',$_REQUEST['groupIDArray']) AS $groupID)
		{
			$Group = new Group($groupID);
			if ($Group && $Group->isValid())
			{
				$this->data[] = array(
					'group_id' => $Group->get('id'),
					'group_name' => $Group->get('name'),
				);
				$_SESSION['delitems']['group'][] = $Group->get('id');
				array_push($this->additional,"\n\t\t\t<p>".$Group->get('name')."</p>");
			}
		}
		$this->render();
		print "\n\t\t\t\t".'<form method="post" action="?node=group&sub=deleteconf">';
		print "\n\t\t\t\t\t<center>".'<input type="submit" value="'._('Are you sure you wish to remove these groups').'?"/></center>';
		print "\n\t\t\t\t</form>";
		print "\n\t\t\t</div>";
	}
	public function deleteconf()
	{
		foreach($_SESSION['delitems']['group'] AS $groupid)
		{
			$Group = new Group($groupid);
			if ($Group && $Group->isValid())
				$Group->destroy();
		}
		unset($_SESSION['delitems']);
		$this->FOGCore->setMessage('All selected items have been deleted');
		$this->FOGCore->redirect('?node='.$this->node);
	}
}
