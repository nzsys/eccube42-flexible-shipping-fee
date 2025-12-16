<?php

namespace Plugin\FlexibleShippingFee\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Repository\Master\PrefRepository;
use Plugin\FlexibleShippingFee\Entity\ShippingArea;
use Plugin\FlexibleShippingFee\Entity\ShippingAreaPref;
use Plugin\FlexibleShippingFee\Entity\ShippingRate;
use Plugin\FlexibleShippingFee\Entity\SizeConfig;
use Plugin\FlexibleShippingFee\Form\Type\Admin\ShippingAreaType;
use Plugin\FlexibleShippingFee\Form\Type\Admin\ShippingRateType;
use Plugin\FlexibleShippingFee\Form\Type\Admin\SizeConfigType;
use Plugin\FlexibleShippingFee\Repository\ShippingAreaRepository;
use Plugin\FlexibleShippingFee\Repository\ShippingAreaPrefRepository;
use Plugin\FlexibleShippingFee\Repository\ShippingRateRepository;
use Plugin\FlexibleShippingFee\Repository\SizeConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConfigController extends AbstractController
{
    /** @var ShippingAreaRepository */
    private $shippingAreaRepository;

    /** @var ShippingAreaPrefRepository */
    private $shippingAreaPrefRepository;

    /** @var ShippingRateRepository */
    private $shippingRateRepository;

    /** @var SizeConfigRepository */
    private $sizeConfigRepository;

    /** @var PrefRepository */
    private $prefRepository;

    public function __construct(
        ShippingAreaRepository $shippingAreaRepository,
        ShippingAreaPrefRepository $shippingAreaPrefRepository,
        ShippingRateRepository $shippingRateRepository,
        SizeConfigRepository $sizeConfigRepository,
        PrefRepository $prefRepository
    ) {
        $this->shippingAreaRepository = $shippingAreaRepository;
        $this->shippingAreaPrefRepository = $shippingAreaPrefRepository;
        $this->shippingRateRepository = $shippingRateRepository;
        $this->sizeConfigRepository = $sizeConfigRepository;
        $this->prefRepository = $prefRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/area", name="flexible_shipping_fee_admin_area")
     * @Template("@FlexibleShippingFee/admin/area_list.twig")
     */
    public function areaList(
        Request $request
    ) {
        $areas = $this->shippingAreaRepository->findAllOrderBySortNo();

        return [
            'areas' => $areas,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/area/new", name="flexible_shipping_fee_admin_area_new")
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/area/{id}/edit", requirements={"id" = "\d+"}, name="flexible_shipping_fee_admin_area_edit")
     * @Template("@FlexibleShippingFee/admin/area_edit.twig")
     */
    public function areaEdit(
        Request $request,
        $id = null
    ) {
        if ($id) {
            $area = $this->shippingAreaRepository->find($id);
            if (!$area) {
                throw new NotFoundHttpException();
            }
            $prefs = $this->shippingAreaPrefRepository->findByAreaId($id);
            $selectedPrefs = array_map(function ($pref) {
                return $pref->getPrefId();
            }, $prefs);
        } else {
            $area = new ShippingArea();
            $sortNo = $this->shippingAreaRepository->getMaxSortNo();
            $area->setSortNo($sortNo + 1);
            $selectedPrefs = [];
        }

        $form = $this->createForm(ShippingAreaType::class, $area);
        $form->get('prefectures')->setData($selectedPrefs);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $area->setUpdateDate(new \DateTime());

            if (!$id) {
                $area->setCreateDate(new \DateTime());
            }

            $this->entityManager->persist($area);
            $this->entityManager->flush();

            if ($id) {
                $this->shippingAreaPrefRepository->deleteByAreaId($id);
            }

            $prefectures = $form->get('prefectures')->getData();
            if ($prefectures && is_array($prefectures)) {
                foreach ($prefectures as $prefId) {
                    if ($prefId && is_numeric($prefId)) {
                        $pref = $this->prefRepository->find($prefId);
                        if ($pref) {
                            $areaPref = new ShippingAreaPref();
                            $areaPref->setShippingArea($area);
                            $areaPref->setPref($pref);
                            $this->entityManager->persist($areaPref);
                        }
                    }
                }
                $this->entityManager->flush();
            }

            $this->addSuccess('保存しました。', 'admin');

            return $this->redirectToRoute('flexible_shipping_fee_admin_area');
        }

        return [
            'form' => $form->createView(),
            'area' => $area,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/area/{id}/delete", requirements={"id" = "\d+"}, name="flexible_shipping_fee_admin_area_delete", methods={"DELETE"})
     */
    public function areaDelete(
        Request $request,
        $id
    ) {
        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('flexible_shipping_fee_admin_area_delete' . $id, $token)) {
            throw new \InvalidArgumentException('CSRF token is invalid.');
        }

        $area = $this->shippingAreaRepository->find($id);
        if (!$area) {
            throw new NotFoundHttpException();
        }

        $this->entityManager->remove($area);
        $this->entityManager->flush();

        $this->addSuccess('削除しました。', 'admin');

        return $this->redirectToRoute('flexible_shipping_fee_admin_area');
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/rate", name="flexible_shipping_fee_admin_rate")
     * @Template("@FlexibleShippingFee/admin/rate_list.twig")
     */
    public function rateList(
        Request $request
    ) {
        $areas = $this->shippingAreaRepository->findAllOrderBySortNo();

        $ratesByArea = [];
        foreach ($areas as $area) {
            $ratesByArea[$area->getId()] = $this->shippingRateRepository->findByAreaId($area->getId());
        }

        return [
            'areas' => $areas,
            'ratesByArea' => $ratesByArea,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/rate/new/{area_id}", requirements={"area_id" = "\d+"}, name="flexible_shipping_fee_admin_rate_new")
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/rate/{id}/edit", requirements={"id" = "\d+"}, name="flexible_shipping_fee_admin_rate_edit")
     * @Template("@FlexibleShippingFee/admin/rate_edit.twig")
     */
    public function rateEdit(
        Request $request,
        $id = null,
        $area_id = null
    ) {
        if ($id) {
            $rate = $this->shippingRateRepository->find($id);
            if (!$rate) {
                throw new NotFoundHttpException();
            }
            $area = $rate->getShippingArea();
        } else {
            if (!$area_id) {
                throw new NotFoundHttpException('エリアIDが指定されていません。');
            }
            $area = $this->shippingAreaRepository->find($area_id);
            if (!$area) {
                throw new NotFoundHttpException('エリアが見つかりません。');
            }
            $rate = new ShippingRate();
            $rate->setShippingArea($area);
            $rate->setAreaId($area->getId());
        }

        $form = $this->createForm(ShippingRateType::class, $rate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rate->setUpdateDate(new \DateTime());

            if ($id) {
                $rate->setShippingArea($area);
            } else {
                $rate->setCreateDate(new \DateTime());
            }

            $this->entityManager->persist($rate);
            $this->entityManager->flush();

            $this->addSuccess('保存しました。', 'admin');

            return $this->redirectToRoute('flexible_shipping_fee_admin_rate');
        }

        return [
            'form' => $form->createView(),
            'rate' => $rate,
            'area' => $area,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/rate/{id}/delete", requirements={"id" = "\d+"}, name="flexible_shipping_fee_admin_rate_delete", methods={"DELETE"})
     */
    public function rateDelete(
        Request $request,
        $id
    ) {
        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('flexible_shipping_fee_admin_rate_delete' . $id, $token)) {
            throw new \InvalidArgumentException('CSRF token is invalid.');
        }

        $rate = $this->shippingRateRepository->find($id);
        if (!$rate) {
            throw new NotFoundHttpException();
        }

        $this->entityManager->remove($rate);
        $this->entityManager->flush();

        $this->addSuccess('削除しました。', 'admin');

        return $this->redirectToRoute('flexible_shipping_fee_admin_rate');
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/size", name="flexible_shipping_fee_admin_size")
     * @Template("@FlexibleShippingFee/admin/size_list.twig")
     */
    public function sizeList(
        Request $request
    ) {
        $configs = $this->sizeConfigRepository->findAllOrderBySortNo();

        return [
            'configs' => $configs,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/size/new", name="flexible_shipping_fee_admin_size_new")
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/size/{id}/edit", requirements={"id" = "\d+"}, name="flexible_shipping_fee_admin_size_edit")
     * @Template("@FlexibleShippingFee/admin/size_edit.twig")
     */
    public function sizeEdit(
        Request $request,
        $id = null
    ) {
        if ($id) {
            $config = $this->sizeConfigRepository->find($id);
            if (!$config) {
                throw new NotFoundHttpException();
            }
        } else {
            $config = new SizeConfig();
            $sortNo = $this->sizeConfigRepository->getMaxSortNo();
            $config->setSortNo($sortNo + 1);
        }

        $form = $this->createForm(SizeConfigType::class, $config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config->setUpdateDate(new \DateTime());

            if (!$id) {
                $config->setCreateDate(new \DateTime());
            }

            $this->entityManager->persist($config);
            $this->entityManager->flush();

            $this->addSuccess('保存しました。', 'admin');

            return $this->redirectToRoute('flexible_shipping_fee_admin_size');
        }

        return [
            'form' => $form->createView(),
            'config' => $config,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/flexible_shipping_fee/size/{id}/delete", requirements={"id" = "\d+"}, name="flexible_shipping_fee_admin_size_delete", methods={"DELETE"})
     */
    public function sizeDelete(
        Request $request,
        $id
    ) {
        $token = $request->get('_token');
        if (!$this->isCsrfTokenValid('flexible_shipping_fee_admin_size_delete' . $id, $token)) {
            throw new \InvalidArgumentException('CSRF token is invalid.');
        }

        $config = $this->sizeConfigRepository->find($id);
        if (!$config) {
            throw new NotFoundHttpException();
        }

        $this->entityManager->remove($config);
        $this->entityManager->flush();

        $this->addSuccess('削除しました。', 'admin');

        return $this->redirectToRoute('flexible_shipping_fee_admin_size');
    }

    /**
     * @Route("/%eccube_admin_route%/store/plugin/FlexibleShippingFee/config", name="plugin_FlexibleShippingFee_config")
     */
    public function index()
    {
        return $this->redirectToRoute('flexible_shipping_fee_admin_area');
    }
}
