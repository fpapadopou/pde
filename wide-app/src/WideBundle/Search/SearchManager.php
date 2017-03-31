<?php

namespace WideBundle\Search;

use WideBundle\Entity\Team;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

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
     * Returns any teams matching the provided search criteria (having a member with `email` email or created
     * after the `date` date).
     *
     * @param $email
     * @param $date
     * @return array
     */
    public function searchTeams($email, $date)
    {
        try {
            $teamEntities = $this->getResults($email, $date);
        } catch (\Exception $exception) {
            return ['success' => false, 'error' => 'An exception occurred - ' . $exception->getMessage()];
        }
        $teams = [];
        foreach ($teamEntities as $team) {
            /** @var Team $team */
            $teams[] = [
                'id' => $team->getId(),
                'created' => $team->getCreated(),
                'members' => $team->getMembersEmails(),
                'folder' => $team->getTeamFolder()
            ];
        }
        return ['success' => true, 'teams' => $teams];
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
        if ($date!= '' && $dateObject !== false) {
            $criteria['date'] = $dateObject;
        }
        return $criteria;
    }

    /**
     * Creates the query, executes it and returns the results from the entity manager.
     * TODO: Can the team id fetching be improved?
     *
     * @param $email
     * @param $date
     * @return array
     */
    private function getResults($email, $date)
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
                return [];
        }

        return $selectFrom->getQuery()->getResult();
    }
}
