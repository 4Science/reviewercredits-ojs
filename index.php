<?php

/**
 * @defgroup plugins_generic_reviewerCredits
 */
 
/**
 * @file plugins/generic/reviewerCredits/index.php
 *
 * Copyright (c) 2015-2018 University of Pittsburgh
 * Copyright (c) 2014-2018 Simon Fraser University Library
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * Contributed by 4Science (http://www.4science.it).
 *
 * @ingroup plugins_generic_reviewerCredits
 * @brief Wrapper for ReviewerCredits plugin.
 *
 */

require_once('ReviewerCredits.inc.php');

return new ReviewerCreditsPlugin();

