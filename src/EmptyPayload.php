<?php

namespace TypesafePayload\TypesafePayload;

/**
 * Allows detecting a distinct empty payload (that doesn't also match to empty strings, false or null)
 * Don't use anywhere else
 * @internal
 */
final class EmptyPayload {}