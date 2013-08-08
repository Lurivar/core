<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Admin\Controller;

use Thelia\Model\CategoryQuery;
use Thelia\Core\Security\Exception\AuthenticationException;

class CategoryController extends BaseAdminController {

	protected function createNewCategory($args) {

		$this->checkAuth("ADMIN", "admin.category.create");

		$this->dispatchEvent("createCategory");

		// At this point, the form has error, and should be redisplayed.
		return $this->render('categories', $args);
	}

	protected function editCategory($args) {

		$this->checkAuth("AMIN", "admin.category.edit");

		return $this->render('edit_category', $args);
	}

	protected function deleteCategory($category_id) {

		$this->checkAuth("AMIN", "admin.category.delete");

		$category = CategoryQuery::create()->findPk($category_id);

		$this->dispatchEvent("deleteCategory");

		// Something was wrong, category was not deleted. Display parent category list
		return $this->render(
			'categories',
			array('current_category_id' => $category->getParent())
		);
	}

	protected function browseCategory($args) {

		$this->checkAuth("AMIN", "admin.catalog.view");

		return $this->render('categories', $args);
	}

	protected function visibilityToggle($args) {

		$this->checkAuth("AMIN", "admin.category.edit");

		$this->dispatchEvent("toggleCategoryVisibility");

		return $this->nullResponse();
	}

	protected function changePosition($args) {

		$this->checkAuth("AMIN", "admin.category.edit");

		$this->dispatchEvent("changeCategoryPosition");

		return $this->render('categories', $args);
	}

	public function indexAction()
	{
		// Show top level categories and products
		$args = array(
				'action' => 'browse',
				'current_category_id' => 0
		);

		return $this->browseCategory($args);
	}

    public function processAction()
    {
    	// Get the current action
    	$action = $this->getRequest()->get('action', 'browse');

    	// Get the category ID
    	$id = $this->getRequest()->get('id', 0);

    	$args = array(
    		'action' 			  => $action,
    		'current_category_id' => $id
    	);

    	try {
    		switch($action) {
	    		case 'browse' : // Browse categories
	    			return $this->browseCategory($args);

	    		case 'create' : // Create a new category
	    			return $this->createNewCategory($args);

	        	case 'edit' : // Edit an existing category
	    			return $this->editCategory($args);

	    		case 'delete' : // Delete an existing category
	    			return $this->deleteCategory($id);

    			case 'visibilityToggle' : // Toggle visibility
	    			return $this->visibilityToggle($id);

    	    	case 'changePosition' : // Change position
	    			return $this->changePosition($args);
	    	}
    	}
    	catch(AuthenticationException $ex) {
    		return $this->render('general_error', array(
    			"error_message" => $ex->getMessage())
    		);
    	}
    }
}