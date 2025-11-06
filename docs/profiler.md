---
id: profiler
title: Profiler
slug: /profiler
---

The profiler will keep track of queries executed in the database system by
creating so called query profiles every time a query is about to be executed.
Then each step of the execution a timestamp will be recorded in the profile.

## Current steps

The steps that are currently recorded are:

-   Start of preparation, the query is being prepared in the `\PDO` instance
-   End of preparation
-   Start of execution, the query is being executed in the `\PDO` instance
-   End of execution
-   Start of hydration, the query starts yielding results
-   End of hydration, there are no more results (this can be skewed if the user
    code using the results takes a long time)
-   When possible, the number of results is recorded

If an exception happens anywhere in the execution process, the query profile
will just stop recording.

Once a query is completely processed, this will result in four measurements:
preparation, execution and hydration durations, and the number of results.
These will be saved in the query profile; note that the query profile starts
empty.

## Setup

By default the `Access\Profiler` is used in the database system, it can be
changed when creating a `Access\Database` instance.

## Extension

When custom query profiles are needed to send the timings to a different place,
to collect metrics, start stopwatches, and any other things; this is possible
by overriding the `createQueryProfileInstance` method when extending the
`Access\Profiler` class. And then using that new class in the setup of the
`Access\Database` class will start creating the custom query profiles.

## Black hole profiler

There is a special profiler that can be used to reduce memory usage in
applications that execute a large number of queries: the `BlackholeProfiler`.
This profiler will still create query profiles to keep up appearances, but will
not keep track of them and throw them in a black hole, never to be seen again.

This profiler also uses the `createQueryProfileInstance` extension hook.

## More information

More information available in `Tests\Base\BaseProfilerTest`.
