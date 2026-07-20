<?php

namespace Peanut\services;

use Tops\db\model\entity\LookupTableEntity;
use Tops\db\model\repository\LookupTableRepository;
use Tops\sys\TPermissionsManager;

class UpdateLookupCommand extends \Tops\services\TServiceCommand
{

    protected function run()
    {
        $request = $this->getRequest();
        if (empty($request->table)) {
            $this->addErrorMessage('Table name is required');
        }
        if (empty($request->item)) {
            $this->addErrorMessage('Item to update is required');
        }

        $repository = new LookupTableRepository($request->table);

        $item = new LookupTableEntity();
        $item->assignFromObject($request->item);
        if ($request->item->id === 0) {
            if ($repository->codeExists($item->code)) {
                $this->addErrorMessage("The code '".$item->code."' is already in use.");
                return;
            }
            $repository->insert($item);
        }
        else {
            $repository->update($item);
        }

        $activeOnly = !empty($request->activeOnly);
        $response = new \stdClass();
        $repository->setLookupInfoColumns(['active']);
        $items = $repository->getLookupList(false,'',LookupTableRepository::sortByName,!$activeOnly);
        $this->setReturnValue($items);
    }
}