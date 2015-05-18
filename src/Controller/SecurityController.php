<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

namespace Crud\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class SecurityController
 */
class SecurityController extends ContainerAware
{

    public function indexAction()
    {
        return $this->render('index.twig');
    }

    /**
     * Pagina de login
     */
    public function loginAction(Request $request)
    {
        return $this->render('login.twig', array(
                'error' => $this->app['security.last_error']($request), // Exibir mensagem de erro
                'last_username' => $this->get('session')->get('_security.last_username'), // Preencher campo com último nome de usuário informado
            )
        );
    }
}
