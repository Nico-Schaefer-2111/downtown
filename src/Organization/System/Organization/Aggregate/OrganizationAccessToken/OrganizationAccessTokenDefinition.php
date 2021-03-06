<?php declare(strict_types=1);

namespace Shopware\Production\Organization\System\Organization\Aggregate\OrganizationAccessToken;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Production\Organization\System\Organization\OrganizationDefinition;

class OrganizationAccessTokenDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'organization_access_token';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return OrganizationAccessTokenEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OrganizationAccessTokenCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required()),

            (new StringField('token', 'token'))->addFlags(new Required()),

            (new FkField('organization_id', 'organizationId', OrganizationDefinition::class))->addFlags(new Required()),
            (new ManyToOneAssociationField('organization', 'organization_id', OrganizationDefinition::class))
        ]);
    }
}
