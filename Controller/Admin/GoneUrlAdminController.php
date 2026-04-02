<?php

namespace RewriteUrl\Controller\Admin;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use RewriteUrl\Model\RewriteurlGoneUrlQuery;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;


#[Route('/admin/module/RewriteUrl/manageGoneUrl', name: 'admin_rewrite_url_manage_gone_url_')]
class GoneUrlAdminController extends BaseAdminController
{
    #[Route('', name: 'show', methods: ['GET'])]
    public function manageGoneUrl(): Response|RedirectResponse
    {
        return $this->render('manage-gone-url');
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['POST'])]
    public function deleteGoneUrl(int $id): JsonResponse
    {
        try {
            RewriteurlGoneUrlQuery::create()->filterById($id)->delete();
        } catch (PropelException) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse(['success' => true]);
    }

    #[Route('/get-rules', name: 'get-rules', methods: ['POST'])]
    public function getDatatableRules(Request $request)
    {
        $requestSearchValue = $request->get('search') ? '%' . $request->get('search')['value'] . '%' : '';
        $recordsTotal = RewriteurlGoneUrlQuery::create()->count();
        $search = RewriteurlGoneUrlQuery::create();
        if ('' !== $requestSearchValue) {
            $search->filterByUrlSource($requestSearchValue, Criteria::LIKE);
        }

        $recordsFiltered = $search->count();

        $orderColumn = $request->get('order')[0]['column'] ?? 0;
        $orderDirection = $request->get('order')[0]['dir'] ?? 'desc';

        // Map column index to database column
        $columnsMap = [
            0 => 'UrlSource',
            1 => 'CreatedAt',
            2 => 'UpdatedAt'
        ];

        if (isset($columnsMap[$orderColumn])) {
            $search->orderBy($columnsMap[$orderColumn], $orderDirection);
        }

        $search
            ->offset($request->get('start'))
            ->limit($request->get('length'));

        $results = $search->find();

        $resultsArray = [];
        foreach ($results as $goneUrl) {
            $id = $goneUrl->getId();
            $resultsArray[] = [
                'Id' => $id,
                'UrlSource' => $goneUrl->getUrlSource(),
                'CreatedAt' => $goneUrl->getCreatedAt() ? $goneUrl->getCreatedAt()->format('Y-m-d H:i:s') : '',
                'UpdatedAt' => $goneUrl->getUpdatedAt() ? $goneUrl->getUpdatedAt()->format('Y-m-d H:i:s') : '',
                'Actions' => '<a href="#" class="js_btn_remove_gone_url btn btn-danger" data-id="' . $id . '"><span class="glyphicon glyphicon-remove"></span></a>',
            ];
        }

        return new JsonResponse([
            'draw' => $request->get('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $resultsArray,
        ]);
    }
}