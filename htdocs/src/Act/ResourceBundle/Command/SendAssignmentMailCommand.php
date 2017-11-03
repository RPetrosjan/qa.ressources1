<?php

namespace Act\ResourceBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Application\Sonata\UserBundle\Entity\User;

/**
 * Class SendAssignmentMailCommand
 *
 * This command is used to send the summary of new assignments
 * for the current week, to the resources involved.
 *
 * It must be used with a CRON to send these mails
 * once every half an our.
 *
 */
class SendAssignmentMailCommand extends ContainerAwareCommand
{
    // The cron interval in seconds - 30 minutes here.
    private $interval = 1800;

    protected function configure()
    {
        $this
            ->setName('act:resources:mail:assignment:send')
            ->setDescription('Send a email to all user who have a new/modified assignement.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $tm = $this->getContainer()->get('act_main.date.manager');
        $weekPlanningManager = $this->getContainer()->get('act_resource.week_planning_manager');

        // Compute modification interval
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $now1HourBefore = clone $now;
        $now1HourBefore->modify('-'.$this->interval.' seconds');

        // Compute week start/end dates
        $start = clone $now;
        $dates = $tm->findFirstAndLastDaysOfWeek($start);

        // Output some information
        $string = "\n".'-------- Check assignments --------'."\n";
        $string .= '-- Week concerned : '.$dates['start']->format('d/m/Y').' to '.$dates['end']->format('d/m/Y')."\n";
        $string .= '-- Changes between : '.$now1HourBefore->format('H:i:s').' and '.$now->format('H:i:s')."\n";
        $output->writeln($string);

        // Retrieve resources with updated/created assignments this week
        $resources = $em->getRepository('ActResourceBundle:Resource')->getCreatedOrUpdatedAssignmentsResources($now1HourBefore, $now, $dates['start'], $dates['end']);
        if (count($resources) == 0) {
            $output->writeln('No resource found'."\n");

            return; // Exit the command
        } else {
            $output->writeln(count($resources).' resource(s) found');
        }

        // Initialize the progress bar
        $progress = $this->getHelperSet()->get('progress');
        $progress->start($output, count($resources));
        $sent = 0;

        // Send the email to all involved users
        foreach ($resources as $resource) {
            $em->clear(); // Clear doctrine object caching

            // Prepare the mail with the template
            $message = \Swift_Message::newInstance()
              ->setSubject($this->getContainer()->get('translator')->trans('mail.assignment.subject'))
              ->setFrom($this->getContainer()->getParameter('mailer_from'))
              ->setTo($resource->getUser()->getEmail())
              ->setContentType("text/html")
              ->setBody(
                $this->getContainer()->get('templating')->render(
                  'ActResourceBundle:Mail:assignment.html.twig',
                  array(
                    'resource' => $resource,
                    'planning' => $weekPlanningManager->getWeekPlanning($resource)
                  )
                )
              )
            ;

            // Send the mail
            $result = $this->getContainer()->get('mailer')->send($message);
            if ($result) {
                $sent++;
            }

            $progress->advance();
        }

        $progress->finish();
        $output->writeln($sent.' mail(s) sent out of '.count($resources)."\n");
    }
}
