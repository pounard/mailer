# IMAP protocol content parsing

This API has been meant to deal with BODYSTRUCTURE and MESSAGE IMAP API
parsing but can be extended and used for various other usages.

Its current state is able to parse any MULTIPART or BODYSTRUCTURE kind of
data as described by the RFC3501 no matter where they come from.

Only limitation is that the input arrays MUST be constructed the following
way:

 *  PARENTHESIS : array

 *  STRING : string

 *  NIL : null

 *  Protocol specifics such as PARAMETER PARENTHESIZED LIST must NOT be
    parsed and parsed accordingly to the upper rules

 *  NESTED PARENTHESIS must be parsed recursively according to the
    upper rules

This API is not really liberal in what it accepts and will raise exceptions
in case of invalid input provided:

 *  \InvalidArgumentException : In case of invalid parameter given violating
    the RFC3501

 *  \OutOfBoundsException : When you attempt to fetch non existing nested
    elements in multipart data

That's it, have fun with it.
