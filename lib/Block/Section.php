<?php


class Block_Section
{

    // If the line after .SH or .SS is one of the following, skip both .SH/.SS line and the one after.
    const skipSectionNameLines = ['.br', '.sp', '.ne', '.PP'];

}
