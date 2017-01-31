<?php


class Block_Section
{

    // If the line after .SH or .SS is one of the following requests skip it:
    const skipSectionNameRequests = ['br', 'sp', 'ne', 'PP', 'TP', 'RS', 'P', 'LP'];

}
