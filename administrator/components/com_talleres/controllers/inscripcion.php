<?php
/**
 * @package Joomla.Administrator
 * @subpackage com_talleres
 * @copyright Copyright (C) 2012 Robert Reimi robert.reimi@gmail.com
 * @license GNU General Public License version 2 or later
 *
 */

// No direct access.
defined('_JEXEC') or die;

jimport('joomla.application.component.controllerform');

class TalleresControllerInscripcion extends JControllerForm
{

    protected $view_list = 'inscripciones';

    /**
     * Method override to check if you can add a new record.
     * Used to check if we have permissions at category level, not required because i dont need category level
     * I leave it here commented for future references
     *
     * @param
    array
    $data
    An array of input data.
     * @return boolean
     */
    protected function allowAdd($data = array())
    {

        return parent::allowAdd($data);

        // Initialise variables.
        $user = JFactory::getUser();
        //$categoryId = JArrayHelper::getValue($data, 'catid', JRequest::getInt('filter_category_id'), 'int');
        $allow = null;

        if ($categoryId) {
            // If the category has been passed in the URL check it.
            $allow = $user->authorise('core.create', $this->option);
        }

        if ($allow === null) {
            // In the absense of better information, revert to the component permissions.
            return parent::allowAdd($data);
        } else {
            return $allow;
        }
    }

    /**
     * Method to check if you can add a new record.
     *
     * @param array $data An array of input data.
     * @param string $key The name of the key for the primary key.
     *
     * @return boolean
     */
    protected function allowEdit($data = array(), $key = 'id')
    {

        return parent::allowEdit($data, $key);

        // Initialise variables.
        $recordId = (int)isset($data[$key]) ? $data[$key] : 0;
        $categoryId = 0;

        if ($recordId) {
            $categoryId = (int)$this->getModel()->getItem($recordId)->catid;
        }

        if ($categoryId) {
            // The category has been set. Check the category permissions.
            return JFactory::getUser()->authorise('core.edit', $this->option . '.category.' . $categoryId);
        } else {
            // Since there is no asset tracking, revert to the component permissions.
            return parent::allowEdit($data, $key);
        }
    }

    public function newsletal() {
        $tmpl = file_get_contents(JPATH_COMPONENT . '/templates/newsletter.html');
        $app = JFactory::getApplication();

        $mailfrom	= $app->getCfg('mailfrom');
        $fromname	= $app->getCfg('fromname');
        $sitename	= $app->getCfg('sitename');

        $name		= "Andrea Lebrun";
        $email		= "tgb.alebrun@gmail.com";
        $subject	= "¡Tenemos nuevo portal web en Granya González!";
        $body		= $tmpl;

        $mail = JFactory::getMailer();
        $mail->addRecipient($email, $name);
        $mail->addReplyTo(array($mailfrom, $fromname));
        $mail->setSender(array($mailfrom, $fromname));
        $mail->setSubject($sitename.': '.$subject);
        $mail->setBody($body);
        $mail->IsHTML(true);

        $sent = $mail->Send();

        //return $sent;

        die("newsletal sent!");
    }

    /**
     * Method to save a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   11.1
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries.
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Initialise variables.
        $app   = JFactory::getApplication();
        $lang  = JFactory::getLanguage();
        $model = $this->getModel();
        $table = $model->getTable();
        $data  = JRequest::getVar('jform', array(), 'post', 'array');
        $checkin = property_exists($table, 'checked_out');
        $context = "$this->option.edit.$this->context";
        $task = $this->getTask();

        // Determine the name of the primary key for the data.
        if (empty($key))
        {
            $key = $table->getKeyName();
        }

        // To avoid data collisions the urlVar may be different from the primary key.
        if (empty($urlVar))
        {
            $urlVar = $key;
        }

        $recordId = JRequest::getInt($urlVar);

        if (!$this->checkEditId($context, $recordId))
        {
            // Somehow the person just went to the form and tried to save it. We don't allow that.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_UNHELD_ID', $recordId));
            $this->setMessage($this->getError(), 'error');

            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_list
                        . $this->getRedirectToListAppend(), false
                )
            );

            return false;
        }

        // Populate the row id from the session.
        $data[$key] = $recordId;

        // The save2copy task needs to be handled slightly differently.
        if ($task == 'save2copy')
        {
            // Check-in the original row.
            if ($checkin && $model->checkin($data[$key]) === false)
            {
                // Check-in failed. Go back to the item and display a notice.
                $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()));
                $this->setMessage($this->getError(), 'error');

                $this->setRedirect(
                    JRoute::_(
                        'index.php?option=' . $this->option . '&view=' . $this->view_item
                            . $this->getRedirectToItemAppend($recordId, $urlVar), false
                    )
                );

                return false;
            }

            // Reset the ID and then treat the request as for Apply.
            $data[$key] = 0;
            $task = 'apply';
        }

        // Access check.
        if (!$this->allowSave($data, $key))
        {
            $this->setError(JText::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'));
            $this->setMessage($this->getError(), 'error');

            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_list
                        . $this->getRedirectToListAppend(), false
                )
            );

            return false;
        }

        // Validate the posted data.
        // Sometimes the form needs some posted data, such as for plugins and modules.
        $form = $model->getForm($data, false);

        if (!$form)
        {
            $app->enqueueMessage($model->getError(), 'error');

            return false;
        }

        // Test whether the data is valid.
        $validData = $model->validate($form, $data);

        // Check for validation errors.
        if ($validData === false)
        {
            // Get the validation messages.
            $errors = $model->getErrors();

            // Push up to three validation messages out to the user.
            for ($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
            {
                if ($errors[$i] instanceof Exception)
                {
                    $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
                }
                else
                {
                    $app->enqueueMessage($errors[$i], 'warning');
                }
            }

            // Save the data in the session.
            $app->setUserState($context . '.data', $data);

            // Redirect back to the edit screen.
            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item
                        . $this->getRedirectToItemAppend($recordId, $urlVar), false
                )
            );

            return false;
        }

        // Attempt to save the data.
        if (!$model->save($validData))
        {
            // Save the data in the session.
            $app->setUserState($context . '.data', $validData);

            // Redirect back to the edit screen.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()));
            $this->setMessage($this->getError(), 'error');

            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item
                        . $this->getRedirectToItemAppend($recordId, $urlVar), false
                )
            );

            return false;
        }

        //Evento para plugin
        JPluginHelper::importPlugin('user');

        $dispatcher	= JDispatcher::getInstance();
        // Trigger the data preparation event.
        $results = $dispatcher->trigger("onInscripcionAfterChange", array($this->option . '.' . $this->name, $validData['id']));

        // Save succeeded, so check-in the record.
        if ($checkin && $model->checkin($validData[$key]) === false)
        {
            // Save the data in the session.
            $app->setUserState($context . '.data', $validData);

            // Check-in failed, so go back to the record and display a notice.
            $this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()));
            $this->setMessage($this->getError(), 'error');

            $this->setRedirect(
                JRoute::_(
                    'index.php?option=' . $this->option . '&view=' . $this->view_item
                        . $this->getRedirectToItemAppend($recordId, $urlVar), false
                )
            );

            return false;
        }

        $this->setMessage(
            JText::_(
                ($lang->hasKey($this->text_prefix . ($recordId == 0 && $app->isSite() ? '_SUBMIT' : '') . '_SAVE_SUCCESS')
                    ? $this->text_prefix
                    : 'JLIB_APPLICATION') . ($recordId == 0 && $app->isSite() ? '_SUBMIT' : '') . '_SAVE_SUCCESS'
            )
        );

        // Redirect the user and adjust session state based on the chosen task.
        switch ($task)
        {
            case 'apply':
                // Set the record data in the session.
                $recordId = $model->getState($this->context . '.id');
                $this->holdEditId($context, $recordId);
                $app->setUserState($context . '.data', null);
                $model->checkout($recordId);

                // Redirect back to the edit screen.
                $this->setRedirect(
                    JRoute::_(
                        'index.php?option=' . $this->option . '&view=' . $this->view_item
                            . $this->getRedirectToItemAppend($recordId, $urlVar), false
                    )
                );
                break;

            case 'save2new':
                // Clear the record id and data from the session.
                $this->releaseEditId($context, $recordId);
                $app->setUserState($context . '.data', null);

                // Redirect back to the edit screen.
                $this->setRedirect(
                    JRoute::_(
                        'index.php?option=' . $this->option . '&view=' . $this->view_item
                            . $this->getRedirectToItemAppend(null, $urlVar), false
                    )
                );
                break;

            default:
                // Clear the record id and data from the session.
                $this->releaseEditId($context, $recordId);
                $app->setUserState($context . '.data', null);

                // Redirect to the list screen.
                $this->setRedirect(
                    JRoute::_(
                        'index.php?option=' . $this->option . '&view=' . $this->view_list
                            . $this->getRedirectToListAppend(), false
                    )
                );
                break;
        }

        // Invoke the postSave method to allow for the child class to access the model.
        $this->postSaveHook($model, $validData);

        $iduser = $this->creaUsuario($data['nombre'], $data['correo'], $data['ci']);

        $this->creaPerfil($iduser, $data['ci'], $data['ciudad'], $data['telefono'], $data['genero']);

        $this->creaGrupo($iduser);

        $this->creaJnewsSubscriber($iduser, $data['nombre'], $data['correo'], $recordId);

        return true;

    }

    // metodo publico custom @larismendi
    public function creaPerfil($iduser, $ci, $city, $phone, $gender){
        $query = "SELECT user_id FROM `#__user_profiles` WHERE `user_id`= '".(int)$iduser."' AND `profile_key` = 'profile.ci' LIMIT 1";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $is_registered = $db->loadObject();

        if($is_registered == null) {
            $query = "INSERT INTO `#__user_profiles` (`user_id`, `profile_key`, `profile_value`, `ordering`) VALUES (".(int)$iduser.", 'profile.ci', '".('"'.$ci.'"')."', 1)";
            $db = JFactory::getDbo();
            $db->setQuery($query);
            $db->query();
        }

        $query = "SELECT user_id FROM `#__user_profiles` WHERE `user_id`= '".(int)$iduser."' AND `profile_key` = 'profile.city' LIMIT 1";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $is_registered = $db->loadObject();

        if($is_registered == null) {
            $query = "INSERT INTO `#__user_profiles` (`user_id`, `profile_key`, `profile_value`, `ordering`) VALUES (".(int)$iduser.", 'profile.city', '".('"'.$city.'"')."', 2)";
            $db = JFactory::getDbo();
            $db->setQuery($query);
            $db->query();
        }

        $query = "SELECT user_id FROM `#__user_profiles` WHERE `user_id`= '".(int)$iduser."' AND `profile_key` = 'profile.phone' LIMIT 1";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $is_registered = $db->loadObject();

        if($is_registered == null) {
            $query = "INSERT INTO `#__user_profiles` (`user_id`, `profile_key`, `profile_value`, `ordering`) VALUES (".(int)$iduser.", 'profile.phone', '".('"'.$phone.'"')."', 3)";
            $db = JFactory::getDbo();
            $db->setQuery($query);
            $db->query();
        }

        $query = "SELECT user_id FROM `#__user_profiles` WHERE `user_id`= '".(int)$iduser."' AND `profile_key` = 'profile.gender' LIMIT 1";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $is_registered = $db->loadObject();

        if($is_registered == null) {
            $query = "INSERT INTO `#__user_profiles` (`user_id`, `profile_key`, `profile_value`, `ordering`) VALUES (".(int)$iduser.", 'profile.gender', '".('"'.$gender.'"')."', 4)";
            $db = JFactory::getDbo();
            $db->setQuery($query);
            $db->query();
        }
    }

    // metodo publico custom @larismendi
    public function creaGrupo($iduser){
        $query = "SELECT user_id FROM `#__user_usergroup_map` WHERE `user_id`= '".(int)$iduser."' AND `group_id` = 2 LIMIT 1";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $is_registered = $db->loadObject();

        if($is_registered == null) {
            $query = "INSERT INTO `#__user_usergroup_map` (`user_id`, `group_id`) VALUES (".(int)$iduser.", 2)";
            $db = JFactory::getDbo();
            $db->setQuery($query);
            $db->query();
        }
    }

    // metodo publico custom @larismendi
    public function creaUsuario($name, $email, $cedula){
        $username = $email; // username is the same as email
        $password = $cedula; // password is the same as cedula

        $query = "SELECT id FROM `#__users` WHERE `username`= '".addslashes($username)."' LIMIT 1";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $is_registered = $db->loadObject();

        if($is_registered == null){

            /*
            I handle this code as if it is a snippet of a method or function!!
            First set up some variables/objects     */

            // get the ACL
            $acl = JFactory::getACL();

            /* get the com_user params */

            jimport('joomla.application.component.helper'); // include libraries/application/component/helper.php
            $usersParams = JComponentHelper::getParams( 'com_users' ); // load the Params

            // "generate" a new JUser Object
            $user = JFactory::getUser(0); // it's important to set the "0" otherwise your admin user information will be loaded

            $data = array(); // array for all user settings

            // get the default usertype
            $usertype = $usersParams->get( 'new_usertype' );
            if (!$usertype) {
                $usertype = 'Registered';
            }

            // set up the "main" user information

            //original logic of name creation
            $data['name'] = $name; // add first- and lastname
            $data['username'] = $username; // add username
            $data['email'] = $email; // add email
            //$data['gid'] = $acl->get_group_id( '', $usertype, 'ARO' );  // generate the gid from the usertype
            
            /* no need to add the usertype, it will be generated automaticaly from the gid */

            $data['password'] = $password; // set the password
            $data['password2'] = $password; // confirm the password
            $data['sendEmail'] = 1; // should the user receive system mails?

            /* Now we can decide, if the user will need an activation */

            /*$useractivation = $usersParams->get( 'useractivation' ); // in this example, we load the config-setting
            if ($useractivation == 1) { // yeah we want an activation
                jimport('joomla.user.helper'); // include libraries/user/helper.php
                $data['block'] = 1; // block the User
                $data['activation'] =JUtility::getHash( JUserHelper::genRandomPassword() ); // set activation hash (don't forget to send an activation email)
            }
            else {*/ // no we need no activation
                $data['block'] = 0; // don't block the user
            //}

            if (!$user->bind($data)) { // now bind the data to the JUser Object, if it not works....
                JError::raiseWarning('', JText::_( $user->getError())); // ...raise an Warning
                return false; // if you're in a method/function return false
            }

            if (!$user->save()) { // if the user is NOT saved...
                JError::raiseWarning('', JText::_( $user->getError())); // ...raise an Warning
                return false; // if you're in a method/function return false
            }

            return $user->id; // else return the new JUser object

        }else{

            return $is_registered->id;

        }
    }

    // metodo publico custom @larismendi
    public function creaJnewsSubscriber($id, $name, $email, $recordId){
        $query = "SELECT id FROM `#__jnews_subscribers` WHERE `email`= '".addslashes($email)."' LIMIT 1";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $is_subscribed = $db->loadObject();

        if($is_subscribed == null){
            $doShowSubscribers = false;
            $newSubscriber = new stdClass;
            $newSubscriber->id =  '' ;
            $newSubscriber->user_id =  $id ;
            $newSubscriber->name =  $name ;
            $newSubscriber->email =  $email ;
            $newSubscriber->ip = jNews_Subscribers::getIP();
            $newSubscriber->receive_html =  1;
            $newSubscriber->confirmed =  1;
            $newSubscriber->blacklist =  0;
            $newSubscriber->timezone = '00:00:00';
            $newSubscriber->language_iso = 'eng';
            $newSubscriber->params = '';
            $newSubscriber->subscribe_date =  time();
            //column
            if($GLOBALS[JNEWS.'level'] > 2){ //check if the version of jnews is pro
                $newSubscriber->column1='';
                $newSubscriber->column2='';
                $newSubscriber->column3='';
                $newSubscriber->column4='';
                $newSubscriber->column5='';
            }
            $lists = jNews_Lists::getLists(0, 0, 1 , '', false, false);
            $queues = '';


            $forms['main'] = " <form action='index.php' method='post' name='adminForm'> \n" ;

            if( $mainframe->isAdmin() || $GLOBALS[JNEWS.'use_backendview'] ){
                backHTML::_header( _JNEWS_MENU_SUBSCRIBERS , $img , $message, $task, $action );
                backHTML::formStart('addsubsback' , 0 ,'' );
                jNews_SubscribersHTML::editSubscriber($newSubscriber, $lists, $queues, $forms, jnews::checkPermissions('admin'), false, false );
            }else{
                backHTML::_header( _JNEWS_MENU_SUBSCRIBERS , $img , $message, $task, $action );
                backHTML::formStart('addsubsfront' , 0 ,'' );
                jNews_SubscribersHTML::editSubscriberFE($newSubscriber, $lists, $queues, $forms, jnews::checkPermissions('admin'), false, false );
            }
        }else{

            $query = "UPDATE `#__jnews_subscribers` SET `user_id` = ".(int) $id." WHERE `email`= '".addslashes($email)."'";
            $db = JFactory::getDbo();
            $db->setQuery($query);
            $db->query();

        }

        $query = "UPDATE `#__inscripcion` SET `jnews_subscriber_id` = (SELECT id FROM `#__jnews_subscribers` WHERE `email`= '".addslashes($email)."') WHERE `id`= '".$recordId."'";
        $db = JFactory::getDbo();
        $db->setQuery($query);
        $db->query();

    }

}