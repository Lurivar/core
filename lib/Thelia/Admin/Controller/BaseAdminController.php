<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*	    email : info@thelia.net                                                      */
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

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;

use Thelia\Form\BaseForm;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Thelia\Core\Security\Exception\AuthenticationTokenNotFoundException;
use Thelia\Model\ConfigQuery;
use Thelia\Core\Security\Exception\AuthenticationException;
use Thelia\Core\Security\SecurityContext;
use Thelia\Tools\URL;
use Thelia\Tools\Redirect;
use Thelia\Core\Template\ParserContext;

/**
 *
 * The defaut administration controller. Basically, display the login form if
 * user is not yet logged in, or back-office home page if the user is logged in.
 *
 * @author Franck Allimant <franck@cqfdev.fr>
 * @author Manuel Raynaud <mraynaud@openstudio.fr>
 */

class BaseAdminController extends ContainerAware
{
	const TEMPLATE_404 = "404.html";

	protected function undefinedAction()
	{
		return new Response($this->renderRaw(self::TEMPLATE_404), 404);
	}


    /**
     * Render the givent template, and returns the result as an Http Response.
     *
     * @param $templateName the complete template name, with extension
     * @param array $args the template arguments
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function render($templateName, $args = array())
    {
        $response = new Response();

        return $response->setContent($this->renderRaw($templateName, $args));
    }

    /**
     * Render the given template, and returns the result as a string.
     *
     * @param $templateName the complete template name, with extension
     * @param array $args the template arguments
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderRaw($templateName, $args = array())
    {
    	$session = $this->getSession();

        $args = array_merge($args, array(
        		'locale' => $session->getLocale(),
        		'lang'   => $session->getLang()
        ));

        try {
        	$data = $this->getParser()->render($templateName, $args);

        	return $data;
        }
        catch (AuthenticationException $ex) {

			// User is not authenticated, and templates requires authentication -> redirect to login page
			// We user login_tpl as a path, not a template.

        	Redirect::exec(URL::absoluteUrl($ex->getLoginTemplate()));
        }
    }

    /**
     * Return the parser context,
     *
     * @return ParserContext
     */
    protected function getParserContext($context = false)
    {
    	return $this->container->get('thelia.parser.context');
    }

    /**
     * Return the security context, by default in admin mode.
     *
     * @return Thelia\Core\Security\SecurityContext
     */
    protected function getSecurityContext($context = false)
    {
    	$securityContext = $this->container->get('thelia.securityContext');

    	$securityContext->setContext($context === false ? SecurityContext::CONTEXT_BACK_OFFICE : $context);

    	return $securityContext;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * Returns the session from the current request
     *
     * @return Ambigous <NULL, \Symfony\Component\HttpFoundation\Session\SessionInterface>
     */
    protected function getSession() {

    	$request = $this->getRequest();

    	return $request->getSession();
    }

    /**
     *
     * @return a ParserInterface instance parser, as configured.
     */
    protected function getParser()
    {
        $parser = $this->container->get("thelia.parser");

		// Define the template thant shoud be used
        $parser->setTemplate(ConfigQuery::read('base_admin_template', 'admin/default'));

        return $parser;
    }

    protected function getFormFactory()
    {
        return BaseForm::getFormFactory($this->getRequest(), ConfigQuery::read("form.secret.admin", md5(__DIR__)));
    }

    protected function getFormBuilder()
    {
        return $this->getFormFactory()->createBuilder("form");
    }

    /**
     * Forwards the request to another controller.
     *
     * @param string $controller The controller name (a string like BlogBundle:Post:index)
     * @param array  $path       An array of path parameters
     * @param array  $query      An array of query parameters
     *
     * @return Response A Response instance
     */
    protected function forward($controller, array $path = array(), array $query = array())
    {
    	$path['_controller'] = $controller;
    	$subRequest = $this->container->get('request')->duplicate($query, null, $path);

    	return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}