<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Application\Form\EntradaForm;
use Application\Entity\Entrada;
use Zend\Mvc\MvcEvent;

class AdminController extends AbstractActionController
{
    public function __construct()
    {
        $events = $this->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH, array($this, 'checkLogin'));
    }

    public function checkLogin()
    {
        $authService = $this->getServiceLocator()->get('Zend\Authentication\AuthenticationService');
        if (!$authService->getIdentity()) {
            return $this->redirect()->toRoute('login');
        }
    }

    public function indexAction()
    {
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        // Le pedimos al EntityManager que nos de el repositorio de entrada
        // El repositorio es el objeto al cual le pedimos los datos que estan 
        // en la base
        $repositorio = $em->getRepository('Application\Entity\Entrada');
        // Para este primer caso obtenemos todas las entradas sin ningún criterio
        // u orden particular
        $entradas = $repositorio->findAll();
        return new ViewModel(['entradas' => $entradas]);
    }

    public function nuevoAction()
    {
        $form = new EntradaForm();
        if ($this->request->isPost()) {
            $data = $this->request->getPost();

            // por ahora seteamos los datos manualmente
            $entrada = new Entrada();
            $entrada->setTitulo($data->titulo); 
            $entrada->setContenido($data->contenido);
                        
            $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
            // le decimos al EntityManager que vamos a guardar el objeto entrada
            $em->persist($entrada);
            // aca ejecuta en la base todos los cambios que le pedímos
            $em->flush();

            // Agregamos el mensaje
            $this->flashMessenger()->addSuccessMessage('Entrada creada correctamente.');

            // redirigimos al listado
            return $this->redirect()->toRoute('admin');
        }
        return new ViewModel(['form' => $form]);
    }

    public function eliminarAction()
    {
        $em = $this->getServiceLocator()->get('Doctrine\ORM\EntityManager');
        $entrada = $em->find('Application\Entity\Entrada', $this->params('id'));
        $mensaje = sprintf("Entrada '%s' eliminada correctamente", $entrada->getTitulo());
        $em->remove($entrada);
        $em->flush();
//        if ($this->request->isXmlHttpRequest()) {
            return new JsonModel([
                'mensaje' => $mensaje,
                'eliminado' => true,
            ]);
  //      }
        $this->flashMessenger()->addSuccessMessage($mensaje);
        return $this->redirect()->toRoute('admin');
    }

}

