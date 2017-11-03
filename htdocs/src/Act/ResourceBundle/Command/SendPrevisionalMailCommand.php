<?php

namespace Act\ResourceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command SendPrevisionalMailCommand
 *
 * Send the previsional team workload for the next 5 weeks.
 * This command has to be executed with a CRON task thrown every Monday at 1 AM.
 */
class SendPrevisionalMailCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('act:resources:mail:previsional:send')
            ->setDescription('Send the next 5 weeks previsional team workload to subscribers')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $rum = $this->getContainer()->get('act_resource.resources_usage_manager');

        // Get the date interval
        $now = new \DateTime("now");
        $start = clone $now;
        $end = clone $now;
        $end->modify('+ 5 weeks');

        // Retrieve all users
        $users = $em->getRepository('ApplicationSonataUserBundle:User')->getPrevisionalSubscribers();

        // Initialize the progress bar
        $progress = $this->getHelperSet()->get('progress');
        $progress->start($output, count($users));

        // For each teams that are involved, compute the workload
        $workloads = array(); $sent = 0; $period = array();
        $teams = $em->getRepository('ActResourceBundle:Team')->getTeamsPrevisionalDistinct();
        foreach ($teams as $team) {

            $workloads[$team->getId()] = array();

            foreach ($team->getResources() as $resource) {
                $data = $rum->getResourceWeeklyChargeForPeriod($resource, $start, $end);
                foreach($data as $weekData) {
                    if (!isset($workloads[$team->getId()][$weekData['week']->format('W/Y')])) {
                        $workloads[$team->getId()][$weekData['week']->format('W/Y')] = array(
                            'week' => $weekData['week'],
                            'affectedTime' => 0,
                            'availableTime' => 0
                        );
                    }

                    if (!isset($period[$weekData['week']->format('W/Y')])) {
                        $period[$weekData['week']->format('W/Y')] = $weekData['week'];
                    }

                    $workloads[$team->getId()][$weekData['week']->format('W/Y')]['affectedTime'] += $weekData['affectedTime'];
                    $workloads[$team->getId()][$weekData['week']->format('W/Y')]['availableTime'] += $weekData['availableTime'];
                }
            }
        }

        // Iterate over users to send mails
        foreach ($users as $user) {
            if (count($user->getPrevisionalTeams()) > 0) {

                $message = \Swift_Message::newInstance()
                    ->setSubject($this->getContainer()->get('translator')->trans('previsional.email').', W'.$start->format('W').' - W'.$end->format('W'))
                    ->setFrom($this->getContainer()->getParameter('mailer_from'))
                    ->setTo($user->getEmail())
                    ->setContentType("text/html")
                    ->setBody(
                        $this->getContainer()->get('templating')->render(
                            'ActResourceBundle:Mail:previsional.html.twig',
                            array(
                                'teams' => $user->getPrevisionalTeams(),
                                'start' => $start,
                                'end' => $end,
                                'period' => $period,
                                'workloads' => $workloads
                            )
                        )
                    )
                ;

                $result = $this->getContainer()->get('mailer')->send($message);
                if ($result) {
                    $sent++;
                }

                $progress->advance();
            }
        }

        $progress->finish();
        $output->writeln($sent.' mail(s) sent out of '.count($users));
    }
}
