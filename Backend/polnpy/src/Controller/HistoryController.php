<?php
namespace App\Controller;

use App\Document\PolenRecord;
use App\Document\PolenDocument;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Monolog\Logger;
use App\Response\CrossJsonResponse;
use Swagger\Annotations as SWG;

class HistoryController
{
    private $registry;
    
    /**
     * @var Logger
     */
    private $logger;
    
    public function __construct(ManagerRegistry $manager, Logger $logger)
    {
        $this->registry = $manager;
        $this->logger = $logger;
    }
    
    /**
     * @SWG\Get(
     *  summary="Get each pollen concentration in a day",
     *  produces={"application/json"},
     *  @SWG\Response(
     *      response=200,
     *      description="Returns the set of pollen concentration for specified date"
     *  ),
     *  @SWG\Parameter(
     *      required=true,
     *      name="date",
     *      in="query",
     *      type="string",
     *      description="The data starting date in format YYYY-MM-DD"
     *  )
     * )
     * @return \App\Response\CrossJsonResponse
     */
    public function dateOverview(Request $request)
    {
        $date = $request->query->get('date', date('Y-m-d'));
        $startDateTime = \DateTime::createFromFormat('Y-m-d', $date);
        $startDateTime->setTime(0, 0, 0, 0);
        
        $endDateTime = \DateTime::createFromFormat('Y-m-d', $date);
        $endDateTime->setTime(23, 59, 59, 999);
        
        $records = $this->registry->getManager()->getRepository(PolenRecord::class)->findInRange($startDateTime, $endDateTime);
        
        $results = [];
        foreach ($records as $record) {
            $results[] = [
                'concentration' => $record->getConcentration(),
                'polen' => $record->getPolen()->getName(),
                'polenId' => $record->getPolen()->getId()
            ];
        }
        
        return new CrossJsonResponse($results, 200);
    }
    
    /**
     * @SWG\Get(
     *  summary="Get history of pollen concentration",
     *  produces={"application/json"},
     *  @SWG\Response(
     *      response=200,
     *      description="Returns the set of pollen concentration for specified type"
     *  ),
     *  @SWG\Parameter(
     *      required=true,
     *      name="type",
     *      in="query",
     *      type="string",
     *      description="The pollen id"
     *  ),
     *  @SWG\Parameter(
     *      required=false,
     *      name="start",
     *      in="query",
     *      type="string",
     *      description="The data starting date in format YYYY-MM-DD"
     *  ),
     *  @SWG\Parameter(
     *      required=false,
     *      name="end",
     *      in="query",
     *      type="string",
     *      description="The data ending date in format YYYY-MM-DD"
     *  )
     * )
     * @return \App\Response\CrossJsonResponse
     */
    public function history(Request $request)
    {
        if (!$request->query->has('type')) {
            return new CrossJsonResponse(
                [
                    'message' => 'Type is required'
                ],
                400
            );
        }
        
        $type = $request->query->get('type');
        $polen = $this->registry->getRepository(PolenDocument::class)->find($type);
        
        if (!$polen) {
            return new CrossJsonResponse(
                [
                    'message' => 'Polen not found'
                ],
                404
            );
        }
        
        try {
            $start = $this->resolveDate($request->query->get('start', null));
            $end = $this->resolveDate($request->query->get('end', null));
        } catch (\Exception $e) {
            return new CrossJsonResponse(
                [
                    'message' => 'Incorrect date format (YYYY-mm-dd)'
                ],
                400
            );
        }
        
        $records = $this->registry->getRepository(PolenRecord::class)->findByPolenAndRange(
            $polen,
            $start,
            $end
            );
        $results = [];
        
        foreach ($records as $record) {
            $results[] = [
                'id' => $record->getId(),
                'concentration' => $record->getConcentration(),
                'date' => $record->getRecordDate()
            ];
        }
        
        return new CrossJsonResponse($results);
    }
    
    protected function resolveDate($date)
    {
        if (!$date) {
            return null;
        }
        
        return \DateTime::createFromFormat('Y-m-d', $date);
    }
}

