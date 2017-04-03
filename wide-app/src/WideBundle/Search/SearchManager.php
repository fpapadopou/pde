<?php

namespace WideBundle\Search;

use WideBundle\Entity\Team;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

/**
 * Class SearchManager
 * Implements team search functionality.
 *
 * @package WideBundle\Search
 */
class SearchManager
{
    /** @var EntityManager $entityManager */
    private $entityManager;

    /**
     * SearchManager constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Returns a database query for searching teams, based on the provided `email` and `date` (optional) parameters.
     * If the parameters are missing, the query will match all teams in database.
     *
     * @param $email
     * @param $date
     * @return array
     */
    public function createTeamSearchQuery($email, $date)
    {
        try {
            $query = $this->buildQuery($email, $date);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => 'An exception occurred - ' . $exception->getMessage()];
        }

        return ['success' => true, 'query' => $query];
    }

    /**
     * Returns the actual search criteria, based on the validity of the form data (email and date).
     *
     * @param $email
     * @param $date
     * @return array
     */
    private function getSearchCriteria($email, $date)
    {
        $criteria = [];
        $team = $this->entityManager->getRepository('WideBundle:User')->findOneBy(['email' => $email]);
        if ($team !== null) {
            $criteria['team'] = $team->getId();
        }
        $dateObject = \DateTime::createFromFormat('Y-m-d', $date);
        if ($date != '' && $dateObject !== false) {
            $criteria['date'] = $dateObject;
        }
        return $criteria;
    }

    /**
     * Returns the query that will be used in the search results pagination. Providing empty email & date parameters
     * will result in all the teams being listed.
     *
     * @param $email
     * @param $date
     * @return Query
     */
    private function buildQuery($email, $date)
    {
        $criteria = $this->getSearchCriteria($email, $date);
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $selectFrom = $queryBuilder->select('t')->from('WideBundle\Entity\Team', 't');

        switch (array_keys($criteria)) {
            case ['team', 'date']:
                /** @var \DateTime $dateObject */
                $dateObject = $criteria['date'];
                $selectFrom->where(
                    $queryBuilder->expr()->eq('t.id', $criteria['team'])
                )->orWhere(
                    $queryBuilder->expr()->gte('t.created', '?1')
                )->setParameter(1, $dateObject->format('Y-m-d'));
                break;
            case ['team']:
                $selectFrom->where($queryBuilder->expr()->eq('t.id', $criteria['team']));
                break;
            case ['date']:
                /** @var \DateTime $dateObject */
                $dateObject = $criteria['date'];
                $selectFrom->where($queryBuilder->expr()->gte('t.created', '?1'))
                    ->setParameter(1, $dateObject->format('Y-m-d'));
                break;
            default:
                // By default, all teams are returned.
                return $selectFrom->getQuery();
        }

        return $selectFrom->getQuery();
    }
}
