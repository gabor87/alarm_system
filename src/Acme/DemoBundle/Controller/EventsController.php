<?php

namespace Acme\DemoBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Acme\DemoBundle\Entity\TraceMobiles;

/**
 *
 */
class EventsController extends Controller
{
    protected function _getArchived($terminalid, $limit, $params = array()) {
        $em = $this->getDoctrine()->getEntityManager();
        $connection = $em->getConnection();

        $where = 'WHERE terminal_id = :terminal_id';
        $params[':terminal_id'] = $terminalid;

        if (isset($params['from']) && strtotime($params['from'])) {
            $where .= ' AND time >= :from';
            $params[':from'] = $params['from'];
        }

        if (isset($params['to']) && strtotime($params['to'])) {
            $where .= ' AND time <= :to';
            $params[':to'] = $params['to'];
        }

        $sql = "SELECT time, type, category FROM 
                    (SELECT time, type, 'notice' as category, terminal_id FROM notices 
                        UNION 
                    SELECT time, type, 'warning' as category, terminal_id FROM warnings) a " . $where . 
                " ORDER BY time DESC LIMIT 200";
        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function archiveAction(Request $request)
    {
        return new JsonResponse($this->_getArchived($request->get('terminalid'), 200, $_GET));
    }

    public function archivemobileAction(Request $request)
    {
        if (empty($_GET['device_id']) || !preg_match('/^\w{32}$/', $_GET['device_id'])) {
            throw $this->createNotFoundException('The page does not exist');
        }

        $trace = new TraceMobiles();
        $trace->setDeviceId($_GET['device_id']);
        $trace->setTime(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($trace);
        $em->flush();

        return new JsonResponse($this->_getArchived($request->get('terminalid'), 100));
    }
}
