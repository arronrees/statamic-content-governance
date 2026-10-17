<?php

namespace Arronrees\ContentGovernance\Rules;

enum OwnerStrategy: string
{
    case Author = 'author';
    case CollectionDefault = 'collection_default';
    case None = 'none';
}
