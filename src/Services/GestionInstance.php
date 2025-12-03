<?php

namespace App\Services;

class GestionInstance
{
    public function resolveInstanceIds($instance): array
    {
        $ids = [];

        // Ajouter l'instance actuelle
        $ids[] = $instance->getId();

        // Parents
        $parent = $instance->getInstanceParent(); //dump('Parent ', $parent);
        while ($parent !== null) {
            $ids[] = $parent->getId();
            $parent = $parent->getInstanceParent();
        }

        // Descendants
        $descendantIds = $this->resolveDescendantsIds($instance);

        // Fusion des Ids
        $ids = array_merge($ids, $descendantIds);

        return array_unique($ids);
    }

    private function resolveDescendantsIds($instance, array $ids=[])
    {

        $enfants = $instance->getInstanceEnfants();
        foreach ($enfants as $child) {
            $ids[] = $child->getId();

            $ids = $this->resolveDescendantsIds($child, $ids);
        }

        return $ids;
    }

}
