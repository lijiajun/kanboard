<?php

namespace Kanboard\Pagination;

use Kanboard\Core\Base;
use Kanboard\Core\Paginator;
use Kanboard\Model\UserModel;

/**
 * Class UserPagination
 *
 * @package Kanboard\Pagination
 * @author  Frederic Guillot
 */
class UserPagination extends Base
{
    /**
     * Get user listing pagination
     *
     * @access public
     * @return Paginator
     */
    public function getListingPaginator()
    {
        return $this->paginator
            ->setUrl('UserListController', 'show')
            ->setMax(30)
            ->setOrder(UserModel::TABLE.'.id')
            ->setQuery($this->userModel->getQuery())
            ->calculate();
    }

    public function getListingPaginatorByName($userName)
    {
        return $this->paginator
           ->setUrl('SearchController', 'userlist',array('search' => $userName))
            ->setMax(30)
            ->setOrder(UserModel::TABLE.'.id')
            ->setQuery($this->userModel->getQuery()->ilike('username', '%'.$userName.'%'))
            ->calculate();
    }
}
