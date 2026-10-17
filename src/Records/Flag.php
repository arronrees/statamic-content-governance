<?php

namespace Arronrees\ContentGovernance\Records;

enum Flag: string
{
    case NeedsChanges = 'needs_changes';
    case Orphaned = 'orphaned';
    case UnpublishedByGovernance = 'unpublished_by_governance';
}
