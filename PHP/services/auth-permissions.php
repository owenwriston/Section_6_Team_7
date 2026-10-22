<?php

const CREATE_PROJECT_ROLES = ['admin', 'project owner'];

function can_create_project(?string $permissions): bool
{
    return in_array(strtolower(trim((string) $permissions)), CREATE_PROJECT_ROLES, true);
}
