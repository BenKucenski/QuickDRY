using System;
using System.Collections;
using System.Collections.Generic;

namespace QuickDRY
{
    /// <summary>
    /// Summary description for SQLResult
    /// </summary>
    public class SQLResult
    {
        public String Error;
        public List<Hashtable> Results;
        public String ConnectionName;
        public String Query;
        public Hashtable Parameters;

        public SQLResult()
        {
            Results = new List<Hashtable>();
            Error = "";
            ConnectionName = "";
            Query = "";
            Parameters = new Hashtable();
        }
    }
}