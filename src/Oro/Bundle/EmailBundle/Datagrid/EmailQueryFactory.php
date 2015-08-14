<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EmailQueryFactory
{
    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var string */
    protected $fromEmailExpression;

    /** @var Registry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param EntityNameResolver        $entityNameResolver
     * @param Registry                  $doctrine
     * @param SecurityFacade            $securityFacade
     */
    public function __construct(
        EmailOwnerProviderStorage $emailOwnerProviderStorage,
        EntityNameResolver $entityNameResolver,
        Registry $doctrine,
        SecurityFacade $securityFacade
    ) {
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->entityNameResolver        = $entityNameResolver;
        $this->doctrine                  = $doctrine;
        $this->securityFacade            = $securityFacade;
    }

    /**
     * @param QueryBuilder $qb                  Source query builder
     * @param string       $emailFromTableAlias EmailAddress table alias of joined Email#fromEmailAddress association
     */
    public function prepareQuery(QueryBuilder $qb, $emailFromTableAlias = 'a')
    {
        $qb->addSelect($this->getFromEmailExpression($emailFromTableAlias));
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $fieldName = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);

            $qb->leftJoin(sprintf('%s.%s', $emailFromTableAlias, $fieldName), $fieldName);
        }
    }

    /**
     * Apply custom ACL checks
     *
     * @param QueryBuilder $qb
     */
    public function applyAcl(QueryBuilder $qb)
    {
        $user = $this->securityFacade->getLoggedUser();
        $organization = $this->securityFacade->getOrganization();

        $mailboxIds = $this->doctrine->getRepository('OroEmailBundle:Mailbox')
             ->findAvailableMailboxIds($user, $organization);
        $uoCheck = $qb->expr()->andX(
            $qb->expr()->eq('eu.owner', ':owner'),
            $qb->expr()->eq('eu.organization ', ':organization')
        );

        if (!empty($mailboxIds)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $uoCheck,
                    $qb->expr()->in('eu.mailboxOwner', ':mailboxIds')
                )
            );
            $qb->setParameter('mailboxIds', $mailboxIds);
        } else {
            $qb->andWhere($uoCheck);
        }
        $qb->setParameter('owner', $user->getId());
        $qb->setParameter('organization', $organization->getId());
    }

    /**
     * @param string $emailFromTableAlias EmailAddress table alias of joined Email#fromEmailAddress association
     *
     * @return string
     */
    protected function getFromEmailExpression($emailFromTableAlias)
    {
        $providers = $this->emailOwnerProviderStorage->getProviders();
        if (empty($providers)) {
            return sprintf('%s.email', $emailFromTableAlias);
        }

        $expressionsByOwner = [];
        foreach ($providers as $provider) {
            $relationAlias                      = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
            $expressionsByOwner[$relationAlias] = $this->entityNameResolver->getNameDQL(
                $provider->getEmailOwnerClass(),
                $relationAlias
            );
        }

        $expression = '';
        foreach ($expressionsByOwner as $alias => $expressionPart) {
            $expression .= sprintf('WHEN %s.%s IS NOT NULL THEN %s ', $emailFromTableAlias, $alias, $expressionPart);
        }
        $expression = sprintf('CASE %sELSE \'\' END', $expression);

        // if has owner then use expression to expose formatted name, use email otherwise
        return sprintf(
            'CONCAT(\'\', CASE WHEN %1$s.hasOwner = true THEN (%2$s) ELSE %1$s.email END) as fromEmailExpression',
            $emailFromTableAlias,
            $expression
        );
    }
}
