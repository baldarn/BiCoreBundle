<?php

namespace Cdf\BiCoreBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class MenuExtension extends \Twig_Extension
{

    protected $em;
    protected $urlgenerator;
    protected $user;

    public function __construct(ObjectManager $em, UrlGeneratorInterface $urlgenerator, TokenStorageInterface $user, $rootpath)
    {
        $this->em = $em;
        $this->urlgenerator = $urlgenerator;
        $this->user = $user;
        $this->rootpath = $rootpath;
    }
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('generamenu', [$this, 'generamenu'], [
                'needs_environment' => true,
                'is_safe' => ['html']
                    ])
        ];
    }
    public function generamenu(\Twig_Environment $environment)
    {
        $router = $this->urlgenerator->match('/')['_route'];
        $rispostahome = array();
        $rispostahome[] = array('percorso' => $this->getUrlObject(getenv('bicorebundle_appname'), $router, ''),
            'nome' => 'Home',
            'target' => '',
        );

        $em = $this->em;
        /* @var $qb \Doctrine\ORM\QueryBuilder */
        $qb = $em->createQueryBuilder();
        $qb->select(array('a'));
        $qb->from('BiCoreBundle:Menuapplicazione', 'a');
        $qb->where('a.attivo = :attivo and (a.padre is null or a.padre = 0)');
        $qb->setParameter('attivo', true);
        $qb->orderBy('a.padre', 'ASC');
        $qb->orderBy('a.ordine', 'ASC');
        $menu = $qb->getQuery()->getResult();

        $risposta = array_merge($rispostahome, $this->getMenu($menu));

        $pathmanuale = $this->rootpath . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'manuale.pdf';
        $username = "";
        $urlLogout = "";

        if (file_exists($pathmanuale)) {
            $risposta[] = array(
                'percorso' => $this->getUrlObject('Manuale', $pathmanuale, '_blank'),
                'nome' => 'Manuale', 'target' => '_blank');
        }

        if ($this->user->getToken()->getProviderKey() === 'secured_area') {
            $username = $this->user->getUsername();
            $urlLogout = $this->generateUrl('fi_autenticazione_signout');
        }

        if ($this->user->getToken()->getProviderKey() === 'main') {
            $username = $this->user->getToken()->getUser()->getUsername();
            $urlLogout = $this->urlgenerator->generate('fos_user_security_logout');
        }

        $risposta[] = array('percorso' => $this->getUrlObject($username, '', ''), 'nome' => $username, 'target' => '',
            'sottolivello' => array(
                array('percorso' => $urlLogout, 'nome' => 'Logout', 'target' => ''),
            ),
        );
        return $environment->render('BiCoreBundle:Menu:menu.html.twig', array('risposta' => $risposta));
    }
    protected function getMenu($menu)
    {
        $risposta = array();
        $em = $this->em;

        foreach ($menu as $item) {
            $visualizzare = true;

            if ($item->isAutorizzazionerichiesta()) {
                $permessi = new \Cdf\BiCoreBundle\Utils\PermessiUtils($this->em, $item->getTag(), $this->user->getToken()->getUser());
                $visualizzare = $permessi->canRead();
            }


            if ($visualizzare) {
                $qb = $em->createQueryBuilder();
                $qb->select(array('a'));
                $qb->from('BiCoreBundle:Menuapplicazione', 'a');
                $qb->where('a.padre = :padre_id');
                $qb->andWhere('a.attivo = :attivo');
                $qb->orderBy('a.padre', 'ASC');
                $qb->orderBy('a.ordine', 'ASC');
                $qb->setParameter('padre_id', $item->getId());
                $qb->setParameter('attivo', true);
                $submenu = $qb->getQuery()->getResult();

                $sottomenutabelle = $this->getSubMenu($submenu);

                $risposta[] = array(
                    'percorso' => $this->getUrlObject($item->getNome(), $item->getPercorso(), $item->getTarget()),
                    'nome' => $item->getNome(),
                    'sottolivello' => $sottomenutabelle,
                    'target' => $item->getTarget(),
                    'notifiche' => $item->hasNotifiche(),
                    'tag' => $item->getTag(),
                    'percorsonotifiche' => $this->getUrlObject($item->getNome(), $item->getPercorsonotifiche(), ''),
                );
                unset($submenu);
                unset($sottomenutabelle);
            }
        }

        return $risposta;
    }
    protected function getSubMenu($submenu)
    {
        $sottomenutabelle = array();
        foreach ($submenu as $subitem) {
            $visualizzare = true;
            if ($subitem->isAutorizzazionerichiesta()) {
                $permessi = new \Cdf\BiCoreBundle\Utils\PermessiUtils($this->em, $subitem->getTag(), $this->user->getToken()->getUser());
                $visualizzare = $permessi->canRead();
            }

            if ($visualizzare) {
                $vettoresottomenu = $this->getMenu(array($subitem));
                $sottomenu = $vettoresottomenu[0];

                if (isset($sottomenu['sottolivello']) && count($sottomenu['sottolivello']) > 0) {
                    $sottolivellomenu = array('sottolivello' => $sottomenu['sottolivello']);
                    $menuobj = $this->getUrlObject($subitem->getNome(), $subitem->getPercorso(), $subitem->getTarget());
                    $sottomenutabelle[] = array_merge($menuobj, $sottolivellomenu);
                } else {
                    $sottomenutabelle[] = $this->getUrlObject($subitem->getNome(), $subitem->getPercorso(), $subitem->getTarget());
                }
            }
        }

        return $sottomenutabelle;
    }
    protected function getUrlObject($nome, $percorso, $target)
    {
        if ($this->routeExists($percorso)) {
            return array('percorso' => $this->urlgenerator->generate($percorso), 'nome' => $nome, 'target' => $target);
        } else {
            return array('percorso' => $percorso, 'nome' => $nome, 'target' => $target);
        }
    }
    protected function routeExists($name)
    {
        $router = $this->urlgenerator;

        if ((null === $router->getRouteCollection()->get($name)) ? false : true) {
            return true;
        } else {
            return false;
        }
    }
    protected function urlExists($name)
    {
        if ($this->checkUrl($name, false)) {
            return true;
        } else {
            if ($this->checkUrl($name, true)) {
                return true;
            } else {
                return false;
            }
        }
    }
    protected function checkUrl($name, $proxy)
    {
        $ch = curl_init($name);

        curl_setopt($ch, CURLOPT_URL, $name);
        if ($proxy) {
            curl_setopt($ch, CURLOPT_PROXY, 'proxyhttp.comune.intranet:8080');
        } else {
            curl_setopt($ch, CURLOPT_PROXY, null);
        }
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); //timeout in seconds
        curl_exec($ch);
        $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($retcode === 200 || $retcode === 401) {
            $exist = true;
        } else {
            $exist = false;
        }
        curl_close($ch);

        return $exist;
    }
}