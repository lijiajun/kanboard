<?php

namespace Kanboard\Core\Security;

/**
 * Role Definitions
 *
 * @package  security
 * @author   Frederic Guillot
 */
class Role
{
    const APP_ADMIN       = 'app-admin';
    const APP_MANAGER     = 'app-manager';
    const APP_USER        = 'app-user';
    const APP_PUBLIC      = 'app-public';

    const SUB_NONE        = null;
    const SUB_CODER       = 'sub-coder';
    const SUB_TESTER      = 'sub-tester';
    const SUB_LEADER      = 'sub-leader';

    const PROJECT_MANAGER = 'project-manager';
    const PROJECT_MEMBER  = 'project-member';
    const PROJECT_VIEWER  = 'project-viewer';
    const PROJECT_EXT_MEMBER  = 'project-ext-member';

    /**
     * Get application roles
     *
     * @access public
     * @return array
     */
    public function getApplicationRoles()
    {
        return array(
            self::APP_ADMIN => t('Administrator'),
            self::APP_MANAGER => t('Manager'),
            self::APP_USER => t('User'),
        );
    }

    public function getApplicationSubRoles()
    {
        return array(
            self::SUB_NONE => t('None'),
            self::SUB_CODER => t('Coder'),
            self::SUB_TESTER => t('Tester'),
            self::SUB_LEADER => t('Leader'),
        );
    }

    /**
     * Get project roles
     *
     * @access public
     * @return array
     */
    public function getProjectRoles()
    {
        return array(
            self::PROJECT_MANAGER => t('Project Manager'),
            self::PROJECT_MEMBER => t('Project Member'),
            self::PROJECT_VIEWER => t('Project Viewer'),
            self::PROJECT_EXT_MEMBER => t('Project Ext Member'),
        );
    }

    /**
     * Check if the given role is custom or not
     *
     * @access public
     * @param  string $role
     * @return bool
     */
    public function isCustomProjectRole($role)
    {
        return ! empty($role) && $role !== self::PROJECT_MANAGER && $role !== self::PROJECT_MEMBER && $role !== self::PROJECT_EXT_MEMBER && $role !== self::PROJECT_VIEWER;
    }

    /**
     * Get role name
     *
     * @access public
     * @param  string $role
     * @return string
     */
    public function getRoleName($role)
    {
        $roles = $this->getApplicationRoles() + $this->getProjectRoles();
        return isset($roles[$role]) ? $roles[$role] : t('Unknown');
    }

    public function getSubRoleName($role)
    {
        $roles = $this->getApplicationSubRoles();
        return isset($roles[$role]) ? $roles[$role] : t('Unknown');
    }
}
