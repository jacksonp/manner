<?php
declare(strict_types=1);

// TODO: handle this differently? Use a flag for preformat?
// See esp. submit.1:
/*
 * environment variable \fBSGE_TASK_ID\fP. The option arguments n, m and s will be available through the environment variables \fBSGE_TASK_FIRST\fP, \fBSGE_TASK_LAST\fP and  \fBSGE_TASK_STEPSIZE\fP.
.sp 1
.nf
.RS
Following restrictions apply to the values n and m:
.sp 1
.RS
1 <= n <= MIN(2^31-1, max_aj_tasks)
1 <= m <= MIN(2^31-1, max_aj_tasks)
n <= m
.RE
.fi
.sp 1
\fImax_aj_tasks\fP is defined in the cluster configuration (see
.M sge_conf 5)
.sp 1
The task id range
 *
 */

class Block_Preformatted implements Block_Template
{

    /**
     * @param DOMElement $parentNode
     * @param array $lines
     * @param array $request
     * @param bool $needOneLineOnly
     * @return DOMElement|null
     * @throws Exception
     */
    static function checkAppend(
        DOMElement $parentNode,
        array &$lines,
        array $request,
        $needOneLineOnly = false
    ): ?DOMElement
    {

        array_shift($lines);

        if (Node::isOrInTag($parentNode, 'pre') || !count($lines)) {
            return null;
        }

        if (Request::peepAt($lines[0])['name'] === 'PP') {
            array_shift($lines);
            $parentNode = Blocks::getBlockContainerParent($parentNode, true);
        } else {
            $parentNode = Blocks::getBlockContainerParent($parentNode, false, true);
        }

        /* @var DomElement $pre */
        $pre = $parentNode->ownerDocument->createElement('pre');

        $pre = $parentNode->appendChild($pre);

        return $pre;

    }

}
